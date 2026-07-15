<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\TaxDueDate;
use App\Models\TaxObligationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxDueDateController extends Controller
{
    public function index(Request $request): View
    {
        $year        = $request->integer('year', date('Y'));
        $obligations = TaxObligationType::orderBy('sort_order')->get();
        $obId        = $request->integer('obligation_id', $obligations->first()?->id);
        $obligation  = $obligations->firstWhere('id', $obId);

        $years   = range(date('Y') - 1, date('Y') + 2);
        $nitKeys = $obligation ? TaxObligationType::nitKeys($obligation->nit_reference) : [];
        $periods = $obligation ? TaxObligationType::periodsFor($obligation->periodicity) : [];

        $existingDates = [];
        if ($obligation) {
            TaxDueDate::where('obligation_type_id', $obligation->id)
                ->where('year', $year)
                ->get()
                ->each(function (TaxDueDate $d) use (&$existingDates) {
                    $existingDates[$d->period_number][$d->nit_key] = $d->due_date->format('Y-m-d');
                });
        }

        return view('admin.tax-due-dates.index', compact(
            'obligations', 'obligation', 'year', 'years', 'nitKeys', 'periods', 'existingDates'
        ));
    }

    public function save(Request $request): RedirectResponse
    {
        $request->validate([
            'obligation_id' => 'required|exists:tax_obligation_types,id',
            'year'          => 'required|integer|min:2020|max:2099',
            'dates'         => 'nullable|array',
            'dates.*.*'     => 'nullable|date',
        ]);

        $obligation = TaxObligationType::findOrFail($request->obligation_id);
        $year       = $request->year;
        $periods    = TaxObligationType::periodsFor($obligation->periodicity);
        $submitted  = $request->input('dates', []);

        $saved = 0;
        foreach ($submitted as $periodNum => $nitDates) {
            $periodLabel = $periods[$periodNum - 1] ?? "Período {$periodNum}";
            foreach ($nitDates as $nitKey => $date) {
                if (! $date) continue;

                TaxDueDate::updateOrCreate(
                    [
                        'obligation_type_id' => $obligation->id,
                        'year'               => $year,
                        'period_number'      => (int) $periodNum,
                        'nit_key'            => $nitKey,
                    ],
                    [
                        'period_label' => $periodLabel,
                        'due_date'     => $date,
                    ]
                );
                $saved++;
            }
        }

        AdminLog::record('updated', 'tax-due-dates', $obligation->id, [], [
            'obligation' => $obligation->code,
            'year'       => $year,
            'dates_saved' => $saved,
        ]);

        return redirect()->route('admin.tax-due-dates.index', [
            'obligation_id' => $obligation->id,
            'year'          => $year,
        ])->with('success', "{$saved} fechas guardadas para {$obligation->name} — {$year}.");
    }

    public function destroyYear(Request $request): RedirectResponse
    {
        $request->validate([
            'obligation_id' => 'required|exists:tax_obligation_types,id',
            'year'          => 'required|integer',
        ]);

        $deleted = TaxDueDate::where('obligation_type_id', $request->obligation_id)
            ->where('year', $request->year)
            ->delete();

        AdminLog::record('deleted', 'tax-due-dates', $request->obligation_id, [
            'year' => $request->year, 'deleted' => $deleted
        ], []);

        return back()->with('success', "{$deleted} fechas eliminadas.");
    }
}
