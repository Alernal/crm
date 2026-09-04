<?php

namespace App\Services;

use App\Models\Client;
use App\Models\TaxDueDate;
use App\Models\TaxEvent;
use App\Models\TaxObligationType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TaxCalendarService
{
    /** Obligaciones de Renta/RST cuya fecha "Activos en el Exterior" hereda, en orden de prioridad. */
    private const ACTIVOS_EXTERIOR_SOURCES = ['RENTA_GC', 'RENTA_JUR', 'RENTA_NAT', 'SIMPLE_DEC'];

    /**
     * Une las obligaciones manuales (TaxEvent: manual/ica/dian) del cliente con las
     * calculadas automáticamente (generateClientCalendar) para un año — fuente única
     * reutilizada por TaxCalendarController::allCalendarItems() (calendario mensual),
     * TaxCalendarController::index() (badges del listado) y TaxAlertsComposer (campana
     * del top bar), que antes cada uno contaba una fuente distinta (manual-only o
     * calculado-only) y por eso mostraban totales de "vencidos" distintos entre sí para
     * el mismo cliente.
     */
    public function combinedEvents(Client $client, int $year): Collection
    {
        $manualEvents = collect($client->taxEvents()->whereYear('due_date', $year)->get());

        // Las filas con source=calculado son la marca de cumplimiento persistida para una
        // ocurrencia CALCULADA (ver TaxCalendarController::completeOccurrence()) — no se
        // muestran como una fila manual aparte, sino que sobreescriben el estado del ítem
        // calculado equivalente (mismo obligation_type + due_date) más abajo.
        $completedCalculated = $manualEvents
            ->where('source', 'calculado')
            ->where('status', 'completed')
            ->keyBy(fn (TaxEvent $e) => $e->obligation_type.'|'.$e->due_date->toDateString());

        $manual = $manualEvents
            ->reject(fn (TaxEvent $e) => $e->source === 'calculado')
            ->map(fn (TaxEvent $e) => [
                'id'              => $e->id,
                'title'           => $e->title,
                'due_date'        => $e->due_date,
                'obligation_type' => $e->obligation_type,
                'period'          => $e->period,
                'status'          => $e->status === 'completed' ? 'completed' : ($e->status === 'overdue' || ($e->status === 'pending' && $e->due_date->isPast()) ? 'overdue' : 'pending'),
                'source'          => $e->source,
                'notes'           => $e->notes,
                'alert_days'      => $e->alert_days,
                'editable'        => true,
            ]);

        $calculated = collect($this->generateClientCalendar($client->id, $year))
            ->values()
            ->map(function (array $e, int $i) use ($completedCalculated, $year) {
                $key       = $e['code'].'|'.$e['due_date'];
                $completed = $completedCalculated->get($key);

                return [
                    'id'              => "calc-{$e['code']}-{$year}-{$i}",
                    'title'           => $e['name'].' — '.$e['period_label'],
                    'due_date'        => Carbon::parse($e['due_date']),
                    'obligation_type' => $e['code'],
                    'period'          => $e['period_label'],
                    'status'          => $completed ? 'completed' : ($e['status'] === 'vencido' ? 'overdue' : 'pending'),
                    'source'          => 'calculado',
                    'notes'           => null,
                    'alert_days'      => 15,
                    'editable'        => false,
                ];
            });

        return $manual->concat($calculated);
    }

    public function generateClientCalendar(int $clientId, int $year): array
    {
        $client = Client::findOrFail($clientId);

        $nit       = preg_replace('/\D/', '', $client->document_number);
        $lastDigit = $nit === '' ? '0' : substr($nit, -1);
        $lastTwo   = (int) (strlen($nit) >= 2 ? substr($nit, -2) : $nit);
        $codes     = $client->tax_responsibilities ?? [];

        $obligations = TaxObligationType::where('active', true)->orderBy('sort_order')->get()->keyBy('code');
        $events      = [];

        foreach ($codes as $code) {
            $ob = $obligations->get($code);
            if (! $ob) continue;

            if ($code === 'ACTIVOS_EXT') {
                $events = array_merge($events, $this->resolveActivosExterior($ob, $codes, $obligations, $year, $lastDigit, $lastTwo));
                continue;
            }

            $dataOb = $ob->alias_of ? $obligations->firstWhere('id', $ob->alias_of) : $ob;
            if (! $dataOb) continue;

            $events = array_merge($events, $this->resolveObligation($ob, $dataOb, $year, $lastDigit, $lastTwo));
        }

        usort($events, fn($a, $b) => strcmp($a['due_date'], $b['due_date']));

        return $events;
    }

    /**
     * Resuelve las fechas de $dataOb (donde viven los TaxDueDate) mostrándolas bajo la
     * identidad de $displayOb (útil para alias como Consumo → IVA_BI).
     */
    private function resolveObligation(TaxObligationType $displayOb, TaxObligationType $dataOb, int $year, string $lastDigit, int $lastTwo): array
    {
        $nitKey = match ($dataOb->nit_reference) {
            'dos_ultimos_digitos' => TaxObligationType::twoDigitPairKeyFor($lastTwo),
            'fecha_fija'          => TaxObligationType::FIXED_DATE_KEY,
            default               => $lastDigit,
        };

        $dueDates = TaxDueDate::where('obligation_type_id', $dataOb->id)
            ->where('year', $year)
            ->where('nit_key', $nitKey)
            ->orderBy('period_number')
            ->get();

        // Fallback: períodos sin fecha específica por dígito usan la fecha única del período (ej. Patrimonio 2ª cuota).
        if ($nitKey !== TaxObligationType::FIXED_DATE_KEY) {
            $coveredPeriods = $dueDates->pluck('period_number')->all();
            $fixedRows = TaxDueDate::where('obligation_type_id', $dataOb->id)
                ->where('year', $year)
                ->where('nit_key', TaxObligationType::FIXED_DATE_KEY)
                ->whereNotIn('period_number', $coveredPeriods)
                ->get();
            $dueDates = $dueDates->concat($fixedRows)->sortBy('period_number')->values();
        }

        return $dueDates->map(fn(TaxDueDate $d) => [
            'code'         => $displayOb->code,
            'name'         => $displayOb->name,
            'period_label' => $d->period_label,
            'due_date'     => $d->due_date->toDateString(),
            'periodicity'  => $displayOb->periodicity,
            'regime'       => $displayOb->regime,
            'status'       => $this->computeStatus($d->due_date),
            'days_left'    => (int) now()->diffInDays($d->due_date, false),
        ])->toArray();
    }

    /** Activos en el Exterior no tiene fecha propia: hereda la de la Renta/RST que el cliente ya tenga marcada. */
    private function resolveActivosExterior(TaxObligationType $ob, array $selectedCodes, Collection $obligations, int $year, string $lastDigit, int $lastTwo): array
    {
        $sourceCode = collect(self::ACTIVOS_EXTERIOR_SOURCES)->first(fn($c) => in_array($c, $selectedCodes, true));
        $source     = $sourceCode ? $obligations->get($sourceCode) : null;
        if (! $source) return [];

        $events = $this->resolveObligation($source, $source, $year, $lastDigit, $lastTwo);

        return array_map(fn($e) => array_merge($e, ['code' => $ob->code, 'name' => $ob->name]), $events);
    }

    private function computeStatus(Carbon $date): string
    {
        if ($date->isPast()) return 'vencido';
        if ($date->diffInDays(now()) <= 15) return 'proximo';
        return 'pendiente';
    }
}
