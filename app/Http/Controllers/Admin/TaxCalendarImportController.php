<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\TaxCalendarImport;
use App\Models\TaxDueDate;
use App\Models\TaxObligationType;
use App\Services\TaxCalendarImportParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TaxCalendarImportController extends Controller
{
    /** Hojas esperadas en el archivo del calendario DIAN, en el orden de la plantilla descargable. */
    private const TEMPLATE_SHEETS = [
        'Renta - Grandes Contribuyentes' => ['Cuota / Evento', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'Renta - Personas Jurídicas'     => ['Cuota / Evento', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'Renta - Personas Naturales'     => ['Dígitos NIT', 'Mes / Hasta', 'Grupo de meses', 'Fecha límite'],
        'IVA Bimestral'                  => ['Período', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'IVA Cuatrimestral'              => ['Período', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'Retención en la Fuente'         => ['Período', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'RST - Anticipo Bimestral'       => ['Período', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'RST - Declaración Anual'        => ['Tipo', 'Mes Límite', 'Dígitos 1-2', 'Dígitos 3-4', 'Dígitos 5-6', 'Dígitos 7-8', 'Dígitos 9-0'],
        'Gasolina y ACPM'                => ['Período', 'Mes Límite', 'Fecha límite'],
        'Carbono'                        => ['Período', 'Mes Límite', 'Fecha límite'],
        'IVA Servicios Exterior'         => ['Período', 'Mes Límite', 'Fecha límite'],
        'Bebidas Ultraprocesadas'        => ['Período', 'Mes Límite', 'Fecha límite'],
        'PES'                            => ['Tipo', 'Período', 'Fecha límite'],
        'Patrimonio'                     => ['Cuota', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'Precios de Transferencia'       => ['Obligación', 'Mes Límite', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
        'Plásticos de un Solo Uso'       => ['Período', 'Nota'],
        'RUB'                            => ['Mes Límite', 'Fecha', 'Nota'],
        'Consumo'                        => ['Nota'],
        'Activos en el Exterior'         => ['Nota'],
    ];

    public function showUpload(): View
    {
        $uploads = TaxCalendarImport::orderByDesc('created_at')->limit(10)->get();
        $years   = range(date('Y'), date('Y') + 2);

        return view('admin.tax-calendar.import', compact('uploads', 'years'));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach (self::TEMPLATE_SHEETS as $title => $headers) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($title);
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
        }

        $writer   = new Xlsx($spreadsheet);
        $filename = 'plantilla-calendario-tributario-dian.xlsx';
        $path     = storage_path("app/tmp-{$filename}");
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $file = $request->file('file');
        $path = $file->store("tax-calendar-imports/{$request->year}", 'local');

        $record = TaxCalendarImport::create([
            'year'          => $request->year,
            'original_name' => $file->getClientOriginalName(),
            'path'          => $path,
            'status'        => 'pending',
        ]);

        try {
            $result = (new TaxCalendarImportParser())->parse(Storage::path($path), (int) $request->year);

            $record->update([
                'status'      => 'reviewed',
                'parsed_rows' => $result['rows'],
                'summary'     => $result['summary'],
                'parse_notes' => count($result['rows']) . ' fechas detectadas en ' . count($result['summary']['matched']) . ' hoja(s).',
            ]);
        } catch (\Throwable $e) {
            $record->update(['status' => 'failed', 'parse_notes' => 'Error al leer el archivo: ' . $e->getMessage()]);

            return redirect()->route('admin.tax-calendar.import')
                ->withErrors(['file' => 'No se pudo leer el archivo. Verifique que sea un Excel válido con la estructura esperada.']);
        }

        AdminLog::record('uploaded', 'tax-calendar-import', $record->id, [], [
            'file' => $file->getClientOriginalName(), 'year' => $request->year,
        ]);

        return redirect()->route('admin.tax-calendar.review', $record);
    }

    public function review(TaxCalendarImport $taxCalendarImport): View
    {
        $obligations = TaxObligationType::orderBy('sort_order')->get()->keyBy('code');
        $rows        = collect($taxCalendarImport->parsed_rows ?? [])->groupBy('code');

        return view('admin.tax-calendar.review', [
            'import'      => $taxCalendarImport,
            'obligations' => $obligations,
            'rowsByCode'  => $rows,
        ]);
    }

    public function confirm(TaxCalendarImport $taxCalendarImport): RedirectResponse
    {
        if ($taxCalendarImport->status === 'imported') {
            return back()->withErrors(['import' => 'Este archivo ya fue importado.']);
        }

        $obligationIds = TaxObligationType::pluck('id', 'code');
        $imported      = 0;

        foreach ($taxCalendarImport->parsed_rows ?? [] as $row) {
            $obId = $obligationIds[$row['code']] ?? null;
            if (! $obId) continue;

            TaxDueDate::updateOrCreate(
                [
                    'obligation_type_id' => $obId,
                    'year'               => $taxCalendarImport->year,
                    'period_number'      => $row['period_number'],
                    'nit_key'            => $row['nit_key'],
                ],
                [
                    'period_label' => $row['period_label'],
                    'due_date'     => $row['due_date'],
                ]
            );
            $imported++;
        }

        $taxCalendarImport->update(['status' => 'imported', 'imported_at' => now()]);
        AdminLog::record('imported', 'tax-calendar-import', $taxCalendarImport->id, [], ['rows' => $imported, 'year' => $taxCalendarImport->year]);

        return redirect()->route('admin.tax-calendar.import')
            ->with('success', "{$imported} fechas importadas para {$taxCalendarImport->year}.");
    }
}
