<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\BudgetSection;
use App\Models\BudgetValue;
use App\Models\BudgetValueEntry;
use App\Models\Client;
use App\Models\ClientBudgetData;
use App\Models\ClientBudgetYearlyData;
use App\Models\PayrollLegalSetting;
use App\Services\Financial\YearlyIndicatorResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderIndex($request, Budget::PRESUPUESTO_TYPES, 'financial.index', 'presupuestos');
    }

    public function statementsIndex(Request $request): View
    {
        return $this->renderIndex($request, Budget::ESTADO_FINANCIERO_TYPES, 'financial.statements.index', 'estados_financieros');
    }

    private function renderIndex(Request $request, array $types, string $view, string $group): View
    {
        $clients = $request->user()->clients()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $clientIds = $clients->pluck('id');

        $budgetsByClient = Budget::where('user_id', $request->user()->id)
            ->whereIn('client_id', $clientIds)
            ->whereIn('type', $types)
            ->get(['id', 'client_id', 'type', 'status', 'name', 'base_year', 'periods_count', 'period_type'])
            ->groupBy('client_id');

        $dataByClient = ClientBudgetData::where('user_id', $request->user()->id)
            ->whereIn('client_id', $clientIds)
            ->get()
            ->keyBy('client_id');

        $totalBudgets = $budgetsByClient->flatten()->count();

        $balanceByClient = $group === 'estados_financieros'
            ? $this->esfBalanceByClient($request, $clientIds)
            : [];

        return view($view, compact('clients', 'budgetsByClient', 'dataByClient', 'totalBudgets', 'balanceByClient', 'group'));
    }

    public function clientBudgets(Request $request, Client $client): View
    {
        return $this->renderClientBudgets($request, $client, Budget::PRESUPUESTO_TYPES, 'financial.client', 'presupuestos');
    }

    public function statementsClient(Request $request, Client $client): View
    {
        return $this->renderClientBudgets($request, $client, Budget::ESTADO_FINANCIERO_TYPES, 'financial.statements.client', 'estados_financieros');
    }

    private function renderClientBudgets(Request $request, Client $client, array $types, string $view, string $group): View
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $budgets = Budget::where('user_id', $request->user()->id)
            ->where('client_id', $client->id)
            ->whereIn('type', $types)
            ->orderByDesc('updated_at')
            ->get();

        $data = ClientBudgetData::where('user_id', $request->user()->id)
            ->where('client_id', $client->id)
            ->first();

        $kpis = [
            'total' => $budgets->count(),
            'draft' => $budgets->where('status', 'draft')->count(),
            'final' => $budgets->where('status', 'final')->count(),
        ];

        $balanceByBudget = [];
        if ($group === 'estados_financieros') {
            foreach ($budgets->where('type', 'esf') as $esf) {
                $esf->load('sections.lines.values');
                $report = $esf->buildEsfReport();
                $balanceByBudget[$esf->id] = $report && collect($report['diferencia'])->every(fn ($d) => abs($d) < 1);
            }
        }

        return view($view, compact('client', 'budgets', 'data', 'kpis', 'balanceByBudget', 'group'));
    }

    /**
     * Estado de cuadre (Activo = Pasivo + Patrimonio) del/los ESF de cada
     * cliente, para el badge del listado de Estados Financieros.
     */
    private function esfBalanceByClient(Request $request, \Illuminate\Support\Collection $clientIds): array
    {
        $balance = [];
        $esfBudgets = Budget::where('user_id', $request->user()->id)
            ->whereIn('client_id', $clientIds)->where('type', 'esf')
            ->with('sections.lines.values')->get();

        foreach ($esfBudgets->groupBy('client_id') as $clientId => $esfs) {
            $balance[$clientId] = $esfs->every(function (Budget $esf) {
                $report = $esf->buildEsfReport();
                return $report && collect($report['diferencia'])->every(fn ($d) => abs($d) < 1);
            });
        }

        return $balance;
    }

    public function create(Request $request): View
    {
        return $this->renderCreate($request, Budget::PRESUPUESTO_TYPES, 'presupuestos');
    }

    /**
     * ESF y ERI ya no se crean por separado: están obligatoriamente
     * conectados en su estructura (la utilidad neta del ERI alimenta el
     * patrimonio del ESF), así que se crean, ven y editan siempre como un
     * solo par — una sola configuración de período compartida, con un
     * selector de pestañas para saltar entre ambos sin perder lo digitado.
     */
    public function statementsCreate(Request $request): View
    {
        $clients     = $request->user()->clients()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $preClientId = $request->query('client_id');
        $preClient   = $preClientId ? $request->user()->clients()->find($preClientId) : null;

        $defaultSections = [
            'esf' => $this->defaultSectionsFor('esf'),
            'eri' => $this->defaultSectionsFor('eri'),
        ];

        return view('financial.statements.create', compact('clients', 'preClient', 'defaultSections'));
    }

    private function statementPairName(string $base, Budget $budget): string
    {
        $first = $budget->calendarYearForPeriod(0);
        $last  = $budget->calendarYearForPeriod($budget->periods_count);

        return $first === $last ? "{$base} {$first}" : "{$base} {$first}-{$last}";
    }

    /**
     * `period_years`/`period_labels` según la periodicidad: anual sigue
     * usando años editables por período (soporta comparativos no
     * consecutivos, p. ej. "2024 vs. 2026"); el resto de periodicidades usa
     * etiquetas de texto libre ("Mes 1", "Trimestre 2"...) y el año de cada
     * período se calcula solo a partir de `base_year` — nunca se piden años
     * no consecutivos ahí, así que no hace falta digitarlos.
     */
    private function statementPeriodFields(array $data, ?int $fallbackBaseYear = null): array
    {
        $isAnnual = $data['period_type'] === 'annual';

        $baseYear = !empty($data['cutoff_date'])
            ? (int) \Carbon\Carbon::parse($data['cutoff_date'])->year
            : ($isAnnual ? ((array_values($data['period_years'] ?? []))[0] ?? ($fallbackBaseYear ?? (int) date('Y'))) : ($fallbackBaseYear ?? (int) date('Y')));

        return [
            'base_year'     => $baseYear,
            'period_years'  => $isAnnual ? array_values($data['period_years'] ?? []) : null,
            'period_labels' => $isAnnual ? null : array_values($data['period_labels'] ?? []),
            'cutoff_date'   => $data['cutoff_date'] ?? null,
        ];
    }

    private function statementSectionRules(string $prefix): array
    {
        return [
            "{$prefix}_sections"                      => 'required|array|min:1',
            "{$prefix}_sections.*.name"               => 'required|string|max:200',
            "{$prefix}_sections.*.statement_role"     => 'nullable|string',
            "{$prefix}_sections.*.lines"               => 'required|array|min:1',
            "{$prefix}_sections.*.lines.*.name"        => 'required|string|max:200',
            "{$prefix}_sections.*.lines.*.sign_negative" => 'nullable|boolean',
            "{$prefix}_sections.*.lines.*.values"      => 'nullable|array',
            "{$prefix}_sections.*.lines.*.values.*"    => 'nullable|numeric',
        ];
    }

    public function statementsStore(Request $request): RedirectResponse
    {
        $data = $request->validate(array_merge([
            'client_id'       => 'required|exists:clients,id',
            'period_type'     => 'required|in:annual,semiannual,four_monthly,quarterly,monthly',
            'periods_count'   => 'required|integer|min:0|max:10',
            'cutoff_date'     => 'nullable|date',
            'period_years'    => 'nullable|array',
            'period_years.*'  => 'nullable|integer|min:1900|max:2200',
            'period_labels'   => 'nullable|array',
            'period_labels.*' => 'nullable|string|max:100',
        ], $this->statementSectionRules('esf'), $this->statementSectionRules('eri')));

        abort_if(
            !$request->user()->clients()->where('id', $data['client_id'])->exists(),
            403
        );

        $esf = DB::transaction(function () use ($data, $request) {
            $common = array_merge([
                'client_id'     => $data['client_id'],
                'base_month'    => 1,
                'period_type'   => $data['period_type'],
                'periods_count' => $data['periods_count'],
                'status'        => 'draft',
            ], $this->statementPeriodFields($data));

            $esf = $request->user()->budgets()->create(array_merge($common, [
                'name' => 'Estado de Situación Financiera',
                'type' => 'esf',
            ]));

            $eri = $request->user()->budgets()->create(array_merge($common, [
                'name' => 'Estado de Resultados',
                'type' => 'eri',
            ]));

            $esf->update(['linked_counterpart_budget_id' => $eri->id, 'name' => $this->statementPairName('Estado de Situación Financiera', $esf)]);
            $eri->update(['linked_counterpart_budget_id' => $esf->id, 'name' => $this->statementPairName('Estado de Resultados', $eri)]);

            $this->persistStructure($esf, $data['esf_sections']);
            $this->persistStructure($eri, $data['eri_sections']);

            return $esf;
        });

        return redirect()->route('financial.statements.show', $esf)
            ->with('success', 'Estado financiero creado. Diligencia las cifras de cada rubro.');
    }

    /**
     * Resuelve el par ESF+ERI a partir de cualquiera de los dos ids —
     * ambos siempre están vinculados entre sí desde que se crean juntos.
     * Los pares heredados de antes de este cambio (ya vinculados) también
     * califican; un ESF/ERI suelto (sin contraparte) no tiene pantalla de
     * par y sigue viéndose con el flujo individual anterior.
     */
    private function resolveStatementPair(Budget $budget): ?array
    {
        if (!in_array($budget->type, Budget::ESTADO_FINANCIERO_TYPES, true) || !$budget->linked_counterpart_budget_id) {
            return null;
        }

        $counterpart = Budget::where('user_id', $budget->user_id)->find($budget->linked_counterpart_budget_id);
        if (!$counterpart || $counterpart->linked_counterpart_budget_id !== $budget->id) {
            return null;
        }

        return $budget->type === 'esf' ? [$budget, $counterpart] : [$counterpart, $budget];
    }

    public function statementsShow(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $pair = $this->resolveStatementPair($budget);
        abort_if(!$pair, 404);
        [$esf, $eri] = $pair;

        $esf->load(['client', 'sections.lines.values']);
        $eri->load(['client', 'sections.lines.values']);

        // Igual que antes: los renglones calculados (utilidad, depreciación,
        // resultados acumulados) se recalculan en cada vista para que el
        // vínculo quede siempre al día sin depender de una acción manual.
        DB::transaction(function () use ($esf, $eri) {
            $this->projectStatements($esf);
            $this->projectStatements($eri);
        });
        $esf->load(['sections.lines.values']);
        $eri->load(['sections.lines.values']);

        $data = ClientBudgetData::where('user_id', $request->user()->id)
            ->where('client_id', $esf->client_id)->first();

        $esfReport       = $esf->buildEsfReport();
        $eriReport       = $eri->buildEriReport();
        $financialRatios = $this->financialRatiosReport($esf, $eri, $data);
        $activeTab       = $budget->type;

        return view('financial.statements.show', compact('esf', 'eri', 'esfReport', 'eriReport', 'financialRatios', 'data', 'activeTab'));
    }

    public function statementsEdit(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $pair = $this->resolveStatementPair($budget);
        abort_if(!$pair, 404);
        [$esf, $eri] = $pair;

        $esf->load('sections.lines.values');
        $eri->load('sections.lines.values');

        $periodYears  = collect(range(0, $esf->periods_count))->map(fn ($i) => $esf->calendarYearForPeriod($i))->all();
        $periodLabels = collect(range(0, $esf->periods_count))->map(function ($i) use ($esf) {
            if (is_array($esf->period_labels) && !empty($esf->period_labels[$i] ?? null)) {
                return $esf->period_labels[$i];
            }
            $word = Budget::PERIOD_LABEL_WORDS[$esf->period_type] ?? 'Período';
            return "{$word} " . ($i + 1);
        })->all();
        $activeTab = $budget->type;

        return view('financial.statements.edit', compact('esf', 'eri', 'periodYears', 'periodLabels', 'activeTab'));
    }

    public function statementsUpdate(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $pair = $this->resolveStatementPair($budget);
        abort_if(!$pair, 404);
        [$esf, $eri] = $pair;

        $data = $request->validate(array_merge([
            'period_type'     => 'required|in:annual,semiannual,four_monthly,quarterly,monthly',
            'periods_count'   => 'required|integer|min:0|max:10',
            'cutoff_date'     => 'nullable|date',
            'period_years'    => 'nullable|array',
            'period_years.*'  => 'nullable|integer|min:1900|max:2200',
            'period_labels'   => 'nullable|array',
            'period_labels.*' => 'nullable|string|max:100',
            'status'          => 'nullable|in:draft,final',
        ], $this->statementSectionRules('esf'), $this->statementSectionRules('eri')));

        DB::transaction(function () use ($esf, $eri, $data) {
            $periodFields = $this->statementPeriodFields($data, $esf->base_year);

            foreach ([$esf, $eri] as $b) {
                $b->update(array_merge([
                    'period_type'   => $data['period_type'],
                    'periods_count' => $data['periods_count'],
                    'status'        => $data['status'] ?? $b->status,
                ], $periodFields));
                $b->lines()->delete();
                $b->sections()->delete();
            }

            $esf->update(['name' => $this->statementPairName('Estado de Situación Financiera', $esf->fresh())]);
            $eri->update(['name' => $this->statementPairName('Estado de Resultados', $eri->fresh())]);

            $this->persistStructure($esf, $data['esf_sections']);
            $this->persistStructure($eri, $data['eri_sections']);
        });

        return redirect()->route('financial.statements.show', $budget)
            ->with('success', 'Estado financiero actualizado.');
    }

    public function statementsDestroy(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $pair = $this->resolveStatementPair($budget);
        abort_if(!$pair, 404);
        [$esf, $eri] = $pair;
        $clientId = $esf->client_id;

        DB::transaction(function () use ($esf, $eri) {
            $esf->delete();
            $eri->delete();
        });

        return redirect()->route('financial.statements.client', $clientId)
            ->with('success', 'Estado financiero eliminado.');
    }

    private function renderCreate(Request $request, array $types, string $group): View
    {
        $clients       = $request->user()->clients()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $preClientId   = $request->query('client_id');
        $preClient     = $preClientId
            ? $request->user()->clients()->find($preClientId)
            : null;

        $siblingBudgets = $preClient
            ? Budget::where('user_id', $request->user()->id)->where('client_id', $preClient->id)
                ->whereIn('type', ['esf', 'eri'])->orderByDesc('id')->get(['id', 'name', 'type'])
            : collect();

        $availableTypes = collect(Budget::TYPES)->only($types)->all();

        $defaultSections = collect($types)->mapWithKeys(fn ($t) => [$t => $this->defaultSectionsFor($t)])->all();

        $suggestedVariables = $preClient ? $this->suggestedVariablesFor($preClient->id) : null;

        return view('financial.create', compact('clients', 'preClient', 'defaultSections', 'siblingBudgets', 'availableTypes', 'group', 'suggestedVariables'));
    }

    /**
     * Inflación y aumento de SMMLV vigentes para el cliente (de "Datos"),
     * usadas en el formulario de creación como punto de partida sugerido y
     * visible para los drivers `inflation`/`smmlv`/`custom_pct` — en vez de un
     * dropdown sin contexto, el contador ve la tasa real antes de aceptarla o
     * cambiarla.
     */
    private function suggestedVariablesFor(int $clientId): array
    {
        $year     = (int) date('Y');
        $resolver = new YearlyIndicatorResolver($clientId);

        return [
            'inflation'      => round($resolver->valueForYear('inflacion', $year), 2),
            'smmlv_increase' => round(($resolver->chainFactor('smmlv', $year - 1, $year) - 1) * 100, 2),
        ];
    }

    /**
     * Un presupuesto (Flujo de Caja) con periodicidad distinta a anual nunca
     * puede cruzar de un año calendario a otro (ver `Budget::maxPeriodsCountFor()`).
     * Estados Financieros quedan fuera: `period_years` ya permite años no
     * consecutivos a propósito (comparativos tipo "2024 vs. 2026").
     */
    private function validatePeriodsWithinYear(Request $request, mixed $value, \Closure $fail, ?string $typeOverride = null): void
    {
        $type = $typeOverride ?? $request->input('type');
        if (in_array($type, Budget::ESTADO_FINANCIERO_TYPES, true)) {
            return;
        }

        $periodType = $request->input('period_type');
        $baseMonth  = (int) $request->input('base_month', 1);
        $max        = Budget::maxPeriodsCountFor($periodType, $baseMonth);

        if ($max !== null && (int) $value > $max) {
            $label = Budget::PERIOD_TYPES[$periodType] ?? $periodType;
            $fail("Con periodicidad \"{$label}\" el número de períodos no puede superar {$max} — un presupuesto no anual no puede cruzar el año del período base.");
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id'     => 'required|exists:clients,id',
            'name'          => 'required|string|max:200',
            'type'          => 'required|in:flujo_caja,esf,eri',
            'linked_counterpart_budget_id'=> 'nullable|exists:budgets,id',
            'base_year'     => 'required|integer|min:2000|max:2100',
            'base_month'    => 'nullable|integer|min:1|max:12',
            'period_type'   => 'required|in:annual,semiannual,four_monthly,quarterly,monthly',
            'periods_count' => ['required', 'integer', 'min:0', 'max:10', function ($attribute, $value, $fail) use ($request) {
                $this->validatePeriodsWithinYear($request, $value, $fail);
            }],
            'period_years'  => 'nullable|array',
            'period_years.*'=> 'nullable|integer|min:1900|max:2200',
            'notes'         => 'nullable|string|max:1000',
            'sections'      => 'required|array|min:1',
            'sections.*.name'                       => 'required|string|max:200',
            'sections.*.is_outflow'                 => 'nullable|boolean',
            'sections.*.statement_role'             => 'nullable|string',
            'sections.*.lines'                      => 'required|array|min:1',
            'sections.*.lines.*.name'               => 'required|string|max:200',
            'sections.*.lines.*.projection_driver'  => 'required|string',
            'sections.*.lines.*.custom_rate'        => 'nullable|numeric',
            'sections.*.lines.*.sign_negative'      => 'nullable|boolean',
            'sections.*.lines.*.base_value'         => 'nullable|numeric',
            'sections.*.lines.*.values'             => 'nullable|array',
            'sections.*.lines.*.values.*'           => 'nullable|numeric',
        ]);

        abort_if(
            !$request->user()->clients()->where('id', $data['client_id'])->exists(),
            403
        );

        $budget = DB::transaction(function () use ($data, $request) {
            $budget = $request->user()->budgets()->create([
                'client_id'      => $data['client_id'],
                'name'           => $data['name'],
                'type'           => $data['type'],
                'linked_counterpart_budget_id' => in_array($data['type'], ['esf', 'eri'], true) ? ($data['linked_counterpart_budget_id'] ?? null) : null,
                'base_year'     => $data['base_year'],
                'base_month'    => $data['type'] === 'flujo_caja' ? ($data['base_month'] ?? 1) : 1,
                'period_type'   => $data['period_type'],
                'periods_count' => $data['periods_count'],
                'period_years'  => in_array($data['type'], Budget::ESTADO_FINANCIERO_TYPES, true) ? array_values($data['period_years'] ?? []) : null,
                'status'        => 'draft',
                'notes'         => $data['notes'] ?? null,
            ]);

            $this->persistStructure($budget, $data['sections']);

            if ($budget->linked_counterpart_budget_id) {
                Budget::where('id', $budget->linked_counterpart_budget_id)
                    ->where('client_id', $budget->client_id)
                    ->update(['linked_counterpart_budget_id' => $budget->id]);
            }

            if ($budget->type === 'flujo_caja') {
                $this->autoProjectFlujoCaja($budget);
            }

            return $budget;
        });

        $isStatement = in_array($budget->type, Budget::ESTADO_FINANCIERO_TYPES, true);

        return redirect()->route('financial.show', $budget)
            ->with('success', $isStatement
                ? 'Estado financiero creado. Diligencia las cifras de cada rubro.'
                : 'Presupuesto creado y proyectado. Configura los Datos del cliente si necesitas ajustar los supuestos.');
    }

    /**
     * Presupuestos (Flujo de Caja) se proyectan automáticamente al crear,
     * editar o ver — no existe un botón "Proyectar" manual (ver `show()`,
     * `store()`, `update()`). Nunca sobrescribe valores marcados
     * `is_manual_override`, así que es seguro correrla en cada request.
     */
    private function autoProjectFlujoCaja(Budget $budget): void
    {
        $data = ClientBudgetData::firstOrCreate(
            ['user_id' => $budget->user_id, 'client_id' => $budget->client_id],
            ['user_id' => $budget->user_id, 'client_id' => $budget->client_id]
        );

        $budget->load(['sections.lines.values']);

        $this->projectFlujoCaja($budget, $data);
    }

    public function show(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['client', 'sections.lines.values', 'linkedCounterpart.sections.lines.values']);

        // ESF/ERI: los renglones calculados (utilidad, depreciación,
        // resultados acumulados) solo se recalculan al editar una celda
        // (`updateValue()`/`renderStatementBody()`) o al vincular por primera
        // vez aquí. Si el usuario vincula dos estados desde el formulario de
        // edición (`update()` reconstruye la estructura sin proyectar) y entra
        // directo a verlo, `buildEsfReport()`/`buildEriReport()` leerían
        // valores nunca calculados. Se recalcula también aquí, igual que
        // `renderStatementBody()`, para que el vínculo quede al día sin
        // depender de una edición manual posterior.
        if (in_array($budget->type, ['esf', 'eri'], true) && $budget->linkedCounterpart) {
            $counterpartId = $budget->linkedCounterpart->id;
            DB::transaction(function () use ($budget, $counterpartId) {
                $this->projectStatements($budget);

                $counterpart = Budget::with(['sections.lines.values', 'linkedCounterpart.sections.lines.values'])
                    ->find($counterpartId);
                $this->projectStatements($counterpart);
            });
            $budget->load(['sections.lines.values', 'linkedCounterpart.sections.lines.values']);
        }

        // Flujo de Caja: sin estado "Proyectado" ni botón manual — cada
        // vista recalcula los rubros no editados a mano (`is_manual_override`)
        // para que un cambio en Datos del cliente (inflación/SMMLV) o en la
        // inversión inicial se refleje sin pedirle al usuario que "proyecte".
        if ($budget->type === 'flujo_caja') {
            DB::transaction(function () use ($budget) {
                $this->autoProjectFlujoCaja($budget);
            });
            $budget->load(['sections.lines.values']);
        }

        $periodLabels     = $budget->getPeriodLabels();
        $data             = ClientBudgetData::where('user_id', $request->user()->id)
                                ->where('client_id', $budget->client_id)->first();
        $cashFlowReport   = $budget->type === 'flujo_caja' ? $budget->buildCashFlowReport() : null;
        $cashFlowDashboard= $cashFlowReport ? $this->cashFlowDashboard($budget, $cashFlowReport, $periodLabels) : null;
        $esfReport        = $budget->type === 'esf' ? $budget->buildEsfReport() : null;
        $eriReport        = $budget->type === 'eri' ? $budget->buildEriReport() : null;

        $financialRatios = null;
        if (in_array($budget->type, Budget::ESTADO_FINANCIERO_TYPES, true) && $budget->linkedCounterpart) {
            $esf = $budget->type === 'esf' ? $budget : $budget->linkedCounterpart;
            $eri = $budget->type === 'eri' ? $budget : $budget->linkedCounterpart;
            if ($esf->type === 'esf' && $eri->type === 'eri') {
                $financialRatios = $this->financialRatiosReport($esf, $eri, $data);
            }
        }

        return view('financial.show', compact('budget', 'periodLabels', 'data', 'cashFlowReport', 'cashFlowDashboard', 'esfReport', 'eriReport', 'financialRatios'));
    }

    public function dashboard(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);
        abort_unless($budget->type === 'flujo_caja', 404);

        $periodLabels     = $budget->getPeriodLabels();
        $cashFlowReport   = $budget->buildCashFlowReport();
        $cashFlowDashboard = $this->cashFlowDashboard($budget, $cashFlowReport, $periodLabels);

        $chartLabels       = $cashFlowReport['chartLabels'];
        $chartPpto         = $cashFlowReport['chartPpto'];
        $chartReal         = $cashFlowReport['chartReal'];
        $chartCumplimiento = $cashFlowReport['chartCumplimiento'];

        return view('financial.dashboard', compact('budget', 'cashFlowDashboard', 'chartLabels', 'chartPpto', 'chartReal', 'chartCumplimiento'));
    }

    /**
     * KPIs y datos de gráfica para el panel "Ver gráfica" de Flujo de Caja:
     * totales Ppto/Real de entradas y salidas, cumplimiento del saldo final,
     * y los 5 rubros con mayor desviación absoluta acumulada (Ppto vs. Real)
     * — da valor de control real, no solo el saldo final que ya se veía antes.
     *
     * Bug real encontrado y corregido: "Cumplimiento del saldo final" siempre
     * comparaba Ppto vs. Real del ÚLTIMO período del presupuesto — que en
     * cualquier presupuesto que proyecta hacia adelante (el caso normal)
     * todavía no ha ocurrido y por lo tanto nunca tiene Real digitado, dando
     * 0.0% de "cumplimiento" de forma permanente y engañosa (parece que el
     * negocio no cumplió nada, cuando en realidad es que ese mes no ha
     * llegado). Ahora se usa el último período que sí tiene al menos un
     * valor Real digitado en el presupuesto; si no hay ninguno todavía, el
     * KPI queda en null (la vista ya lo muestra como "—", no como 0.0%).
     */
    private function cashFlowDashboard(Budget $budget, array $cashFlowReport, array $periodLabels): array
    {
        $rows    = $cashFlowReport['rows'];
        $periods = array_keys($periodLabels);

        $entradasTotal = collect($rows)->firstWhere('label', 'Total de entradas');
        $salidasTotal  = collect($rows)->firstWhere('label', 'Total de salidas');
        $saldoFinalRow = collect($rows)->last(fn ($r) => $r['type'] === 'final');

        $sum = fn (?array $row, string $vt) => $row
            ? array_sum(array_map(fn ($v) => $v[$vt], $row['values']))
            : 0.0;

        $lastPeriod     = max($periods);
        $lastRealPeriod = BudgetValue::where('budget_id', $budget->id)
            ->where('value_type', 'actual')
            ->max('period_index');

        $finalPpto = $saldoFinalRow['values'][$lastPeriod]['ppto'] ?? 0.0;
        $finalReal = $lastRealPeriod !== null ? ($saldoFinalRow['values'][$lastRealPeriod]['real'] ?? 0.0) : null;

        // El % de cumplimiento compara Real vs. Ppto del MISMO período
        // ($lastRealPeriod) — comparar el Real ya vivido contra el Ppto del
        // último período del presupuesto (con frecuencia todavía en el
        // futuro) mezclaría dos períodos distintos y daría un % sin sentido.
        $pptoAtLastReal = $lastRealPeriod !== null
            ? ($saldoFinalRow['values'][$lastRealPeriod]['ppto'] ?? 0.0)
            : null;

        $topVariances = collect($rows)
            ->filter(fn ($r) => $r['type'] === 'data')
            ->map(function ($r) {
                $diff = 0.0;
                foreach ($r['values'] as $v) {
                    $diff += abs(($v['real'] ?? 0) - ($v['ppto'] ?? 0));
                }
                return ['label' => $r['label'], 'diff' => round($diff, 2), 'is_outflow' => $r['is_outflow'] ?? false];
            })
            ->filter(fn ($v) => $v['diff'] > 0.0)
            ->sortByDesc('diff')
            ->take(5)
            ->values()
            ->all();

        return [
            'totalEntradasPpto' => $sum($entradasTotal, 'ppto'),
            'totalEntradasReal' => $sum($entradasTotal, 'real'),
            'totalSalidasPpto'  => $sum($salidasTotal, 'ppto'),
            'totalSalidasReal'  => $sum($salidasTotal, 'real'),
            'finalPpto'         => $finalPpto,
            'finalReal'         => $finalReal,
            'lastRealPeriodLabel' => $lastRealPeriod !== null ? ($periodLabels[$lastRealPeriod] ?? null) : null,
            'cumplimiento'      => ($pptoAtLastReal !== null && $pptoAtLastReal != 0.0)
                ? round(($finalReal / $pptoAtLastReal) * 100, 1)
                : null,
            'topVariances'      => $topVariances,
            'chartLabels'       => array_values($periodLabels),
            'entradasPptoSerie' => collect($periods)->map(fn ($p) => round($entradasTotal['values'][$p]['ppto'] ?? 0))->all(),
            'entradasRealSerie' => collect($periods)->map(fn ($p) => round($entradasTotal['values'][$p]['real'] ?? 0))->all(),
            'salidasPptoSerie'  => collect($periods)->map(fn ($p) => round($salidasTotal['values'][$p]['ppto'] ?? 0))->all(),
            'salidasRealSerie'  => collect($periods)->map(fn ($p) => round($salidasTotal['values'][$p]['real'] ?? 0))->all(),
        ];
    }

    public function edit(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['sections.lines.values']);
        $clients  = $request->user()->clients()->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        $siblingBudgets = Budget::where('user_id', $request->user()->id)->where('client_id', $budget->client_id)
            ->whereIn('type', ['esf', 'eri'])->where('id', '!=', $budget->id)
            ->orderByDesc('id')->get(['id', 'name', 'type']);

        return view('financial.edit', compact('budget', 'clients', 'siblingBudgets'));
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'linked_counterpart_budget_id'=> 'nullable|exists:budgets,id',
            'base_year'     => 'required|integer|min:2000|max:2100',
            'base_month'    => 'nullable|integer|min:1|max:12',
            'period_type'   => 'required|in:annual,semiannual,four_monthly,quarterly,monthly',
            'periods_count' => ['required', 'integer', 'min:0', 'max:10', function ($attribute, $value, $fail) use ($request, $budget) {
                $this->validatePeriodsWithinYear($request, $value, $fail, $budget->type);
            }],
            'period_years'  => 'nullable|array',
            'period_years.*'=> 'nullable|integer|min:1900|max:2200',
            'notes'         => 'nullable|string|max:1000',
            'status'        => 'nullable|in:draft,final',
            'sections'      => 'required|array|min:1',
            'sections.*.name'                       => 'required|string|max:200',
            'sections.*.is_outflow'                 => 'nullable|boolean',
            'sections.*.statement_role'             => 'nullable|string',
            'sections.*.lines'                      => 'required|array|min:1',
            'sections.*.lines.*.name'               => 'required|string|max:200',
            'sections.*.lines.*.projection_driver'  => 'required|string',
            'sections.*.lines.*.custom_rate'        => 'nullable|numeric',
            'sections.*.lines.*.sign_negative'      => 'nullable|boolean',
            'sections.*.lines.*.base_value'         => 'nullable|numeric',
            'sections.*.lines.*.values'             => 'nullable|array',
            'sections.*.lines.*.values.*'           => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($budget, $data) {
            $oldCounterpartId = $budget->linked_counterpart_budget_id;

            $budget->update([
                'name'          => $data['name'],
                'linked_counterpart_budget_id' => in_array($budget->type, ['esf', 'eri'], true) ? ($data['linked_counterpart_budget_id'] ?? null) : null,
                'base_year'     => $data['base_year'],
                'base_month'    => $budget->type === 'flujo_caja' ? ($data['base_month'] ?? 1) : 1,
                'period_type'   => $data['period_type'],
                'periods_count' => $data['periods_count'],
                'period_years'  => in_array($budget->type, Budget::ESTADO_FINANCIERO_TYPES, true) ? array_values($data['period_years'] ?? []) : null,
                // El formulario de edición ya no tiene un campo de notas — no
                // sobrescribir las que se hayan guardado antes de quitarlo.
                'notes'         => $data['notes'] ?? $budget->notes,
                'status'        => $data['status'] ?? $budget->status,
            ]);

            // Mantiene la pareja ESF↔ERI simétrica: si cambió el vínculo,
            // desvincula al contraparte anterior y vincula al nuevo.
            if ($oldCounterpartId && $oldCounterpartId !== $budget->linked_counterpart_budget_id) {
                Budget::where('id', $oldCounterpartId)->where('linked_counterpart_budget_id', $budget->id)
                    ->update(['linked_counterpart_budget_id' => null]);
            }
            if ($budget->linked_counterpart_budget_id) {
                Budget::where('id', $budget->linked_counterpart_budget_id)
                    ->where('client_id', $budget->client_id)
                    ->update(['linked_counterpart_budget_id' => $budget->id]);
            }

            // Rebuild sections and lines
            $budget->lines()->delete();
            $budget->sections()->delete();

            $this->persistStructure($budget, $data['sections']);

            if ($budget->type === 'flujo_caja') {
                $this->autoProjectFlujoCaja($budget);
            }
        });

        return redirect()->route('financial.show', $budget)
            ->with('success', $this->budgetNoun($budget, true) . ' actualizado.');
    }

    private function persistStructure(Budget $budget, array $sections): void
    {
        foreach ($sections as $sIdx => $sData) {
            $section = $budget->sections()->create([
                'name'           => $sData['name'],
                'sort_order'     => $sIdx,
                'is_outflow'     => !empty($sData['is_outflow']),
                'statement_role' => $sData['statement_role'] ?? null,
            ]);

            foreach ($sData['lines'] as $lIdx => $lData) {
                $line = $section->lines()->create([
                    'budget_id'          => $budget->id,
                    'name'               => $lData['name'],
                    'sort_order'         => $lIdx,
                    // Estados Financieros no envían driver (cifras siempre
                    // digitadas por período, sin proyección) — "manual" es el
                    // valor neutro correcto para esas líneas.
                    'projection_driver'  => $lData['projection_driver'] ?? 'manual',
                    'custom_rate'        => $lData['custom_rate'] ?? null,
                    'sign_negative'      => !empty($lData['sign_negative']),
                    'is_subtotal'        => false,
                ]);

                // Estados Financieros (ESF/ERI): cifras reales digitadas por
                // período en el propio formulario (sin driver de proyección),
                // llegan como `values[periodo] => valor` en vez de un solo
                // `base_value`. Cada período con valor queda manual — nunca se
                // sobrescribe con "Actualizar vínculos" salvo los 3 renglones
                // calculados (`projectStatements()`), que llegan vacíos aquí.
                if (isset($lData['values']) && is_array($lData['values'])) {
                    foreach ($lData['values'] as $periodIndex => $val) {
                        if ($val === null || $val === '') {
                            continue;
                        }
                        BudgetValue::create([
                            'budget_id'          => $budget->id,
                            'line_id'            => $line->id,
                            'period_label'       => $budget->buildPeriodLabel((int) $periodIndex),
                            'period_index'       => (int) $periodIndex,
                            'value'              => (float) $val,
                            'is_manual_override' => true,
                        ]);
                    }
                    continue;
                }

                $baseValue = (float) ($lData['base_value'] ?? 0);

                BudgetValue::create([
                    'budget_id'    => $budget->id,
                    'line_id'      => $line->id,
                    'period_label' => $budget->buildPeriodLabel(0),
                    'period_index' => 0,
                    'value'        => $baseValue,
                    // Un valor base en 0 se trata como "todavía sin definir": deja
                    // el período 0 libre para que el flujo de caja lo auto-complete
                    // (Aportes de socios, Compra de PP&E...) al Proyectar. Un valor
                    // explícito (o el saldo inicial) sí queda protegido como manual.
                    'is_manual_override' => $baseValue != 0.0,
                ]);
            }
        }
    }

    public function printView(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['client', 'sections.lines.values']);
        $user             = $request->user();
        $periodLabels     = $budget->getPeriodLabels();
        $cashFlowReport   = $budget->type === 'flujo_caja' ? $budget->buildCashFlowReport() : null;
        $esfReport        = $budget->type === 'esf' ? $budget->buildEsfReport() : null;
        $eriReport        = $budget->type === 'eri' ? $budget->buildEriReport() : null;
        $orientation      = $this->orientationFor($periodLabels, $cashFlowReport !== null);
        $paperSize        = $orientation === 'portrait' ? 'letter' : 'legal';

        return view('financial.print', compact('budget', 'user', 'periodLabels', 'orientation', 'paperSize', 'cashFlowReport', 'esfReport', 'eriReport'));
    }

    public function pdf(Request $request, Budget $budget): Response
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['client', 'sections.lines.values']);
        $user             = $request->user();
        $periodLabels     = $budget->getPeriodLabels();
        $cashFlowReport   = $budget->type === 'flujo_caja' ? $budget->buildCashFlowReport() : null;
        $esfReport        = $budget->type === 'esf' ? $budget->buildEsfReport() : null;
        $eriReport        = $budget->type === 'eri' ? $budget->buildEriReport() : null;
        $orientation      = $this->orientationFor($periodLabels, $cashFlowReport !== null);
        $paperSize        = $orientation === 'portrait' ? 'letter' : 'legal';

        $pdf = Pdf::loadView('financial.pdf', compact('budget', 'user', 'periodLabels', 'orientation', 'cashFlowReport', 'esfReport', 'eriReport'))
            ->setPaper($paperSize, $orientation);

        $fileSlug = \Illuminate\Support\Str::slug("presupuesto-{$budget->client->name}-{$budget->name}");

        return $pdf->download("{$fileSlug}.pdf");
    }

    /**
     * Vertical para presupuestos con pocos periodos, horizontal cuando la
     * tabla tiene demasiadas columnas para caber legible en una hoja vertical.
     * El flujo de caja siempre usa horizontal: cada período ocupa 3 columnas
     * (Ppto/Real/Var%), por lo que vertical solo cabría con 1-2 períodos.
     */
    private function orientationFor(array $periodLabels, bool $isCashFlow = false): string
    {
        if ($isCashFlow) {
            return 'landscape';
        }

        return count($periodLabels) <= 5 ? 'portrait' : 'landscape';
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);
        $clientId    = $budget->client_id;
        $isStatement = in_array($budget->type, Budget::ESTADO_FINANCIERO_TYPES, true);
        $budget->delete();

        return redirect()->route($isStatement ? 'financial.statements.client' : 'financial.client', $clientId)
            ->with('success', ($isStatement ? 'Estado financiero' : 'Presupuesto') . ' eliminado.');
    }

    public function project(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $data = ClientBudgetData::firstOrCreate(
            ['user_id' => $request->user()->id, 'client_id' => $budget->client_id],
            ['user_id' => $request->user()->id, 'client_id' => $budget->client_id]
        );

        $budget->load(['client', 'sections.lines.values', 'linkedCounterpart.sections.lines.values']);

        DB::transaction(function () use ($budget, $data) {
            if ($budget->type === 'flujo_caja') {
                $this->projectFlujoCaja($budget, $data);
            } elseif (in_array($budget->type, ['esf', 'eri'], true)) {
                $this->projectGeneric($budget, $data);
                $this->projectStatements($budget);
            } else {
                $this->projectGeneric($budget, $data);
            }
        });

        $isStatement = in_array($budget->type, ['esf', 'eri'], true);

        return redirect()->route('financial.show', $budget)
            ->with('success', $isStatement
                ? 'Vínculos actualizados correctamente.'
                : 'Presupuesto proyectado correctamente.');
    }

    /**
     * Marca el presupuesto como "Aprobado" — el estado que se acciona una
     * vez el presupuesto se socializa y se envía para aprobación y
     * seguimiento. No cambia ninguna cifra, solo el estado.
     */
    public function approve(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->update(['status' => 'final']);

        return redirect()->route('financial.show', $budget)
            ->with('success', $this->budgetNoun($budget, true) . ' aprobado.');
    }

    public function updateValue(Request $request, Budget $budget): JsonResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'line_id'      => 'required|exists:budget_lines,id',
            'period_index' => 'required|integer|min:0',
            'value'        => 'nullable|numeric',
            'value_type'   => 'nullable|in:budgeted,actual',
        ]);

        $valueType = $data['value_type'] ?? 'budgeted';
        $line = BudgetLine::where('id', $data['line_id'])->where('budget_id', $budget->id)->firstOrFail();
        $line->load('values');

        $payload = [
            'budget_id'          => $budget->id,
            'period_label'       => $budget->buildPeriodLabel($data['period_index']),
            'is_manual_override' => true,
            'value'              => $data['value'] ?? 0,
        ];

        BudgetValue::updateOrCreate(
            ['line_id' => $line->id, 'period_index' => $data['period_index'], 'value_type' => $valueType],
            $payload
        );

        if ($budget->type === 'flujo_caja') {
            $budget->load('sections.lines.values');
            $this->recomputeFlujoCajaBalances($budget, $valueType);

            return response()->json(['ok' => true, 'html' => $this->renderCashFlowBody($budget)]);
        }

        if (in_array($budget->type, ['esf', 'eri'], true)) {
            return response()->json(array_merge(['ok' => true], $this->renderStatementBody($budget)));
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Recalcula y renderiza solo el <tbody> de la tabla Ppto/Real de un
     * presupuesto de flujo de caja, para que el frontend lo reemplace sin
     * recargar la página completa (mismo patrón que renderStatementBody()) —
     * así el saldo final y el % de cumplimiento quedan al día tras editar
     * una celda "Real" sin perder la posición del scroll.
     */
    private function renderCashFlowBody(Budget $budget): string
    {
        $fresh = Budget::with('sections.lines.values')->find($budget->id);
        $cashFlowReport = $fresh->buildCashFlowReport();

        return view('financial._cashflow-body', [
            'rows'         => $cashFlowReport['rows'],
            'periodLabels' => $fresh->getPeriodLabels(),
        ])->render();
    }

    /**
     * Trazabilidad de "Real": lista los movimientos (fecha/tercero/
     * descripción/valor) cargados para una línea+período, para poblar el
     * modal al abrirlo.
     */
    public function valueEntriesIndex(Request $request, Budget $budget): JsonResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'line_id'      => 'required|exists:budget_lines,id',
            'period_index' => 'required|integer|min:0',
        ]);

        $line = BudgetLine::where('id', $data['line_id'])->where('budget_id', $budget->id)->firstOrFail();

        $entries = $line->valueEntries()
            ->where('period_index', $data['period_index'])
            ->get(['id', 'entry_date', 'tercero', 'description', 'value']);

        return response()->json(['entries' => $entries]);
    }

    /**
     * Guarda de una sola vez todos los movimientos de "Real" para una
     * línea+período (reemplaza los anteriores por la lista completa
     * enviada — más simple y seguro que diferenciar altas/bajas) y
     * actualiza el valor agregado de la celda con la suma, que es la
     * misma cifra que ya usa `updateValue()`/`recomputeFlujoCajaBalances()`
     * para el resto del flujo de caja.
     */
    public function valueEntriesSave(Request $request, Budget $budget): JsonResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'line_id'                 => 'required|exists:budget_lines,id',
            'period_index'            => 'required|integer|min:0',
            'entries'                 => 'array',
            'entries.*.entry_date'    => 'required|date',
            'entries.*.tercero'       => 'nullable|string|max:255',
            'entries.*.description'   => 'nullable|string|max:255',
            'entries.*.value'         => 'required|numeric',
        ]);

        $line = BudgetLine::where('id', $data['line_id'])->where('budget_id', $budget->id)->firstOrFail();
        $entries = $data['entries'] ?? [];

        DB::transaction(function () use ($budget, $line, $data, $entries) {
            $line->valueEntries()->where('period_index', $data['period_index'])->delete();

            foreach ($entries as $entry) {
                $line->valueEntries()->create([
                    'period_index' => $data['period_index'],
                    'entry_date'   => $entry['entry_date'],
                    'tercero'      => $entry['tercero'] ?? null,
                    'description'  => $entry['description'] ?? null,
                    'value'        => $entry['value'],
                ]);
            }

            $sum = collect($entries)->sum('value');

            BudgetValue::updateOrCreate(
                ['line_id' => $line->id, 'period_index' => $data['period_index'], 'value_type' => 'actual'],
                [
                    'budget_id'          => $budget->id,
                    'period_label'       => $budget->buildPeriodLabel($data['period_index']),
                    'is_manual_override' => true,
                    'value'              => $sum,
                ]
            );
        });

        $budget->load('sections.lines.values');
        $this->recomputeFlujoCajaBalances($budget, 'actual');

        return response()->json(['ok' => true, 'html' => $this->renderCashFlowBody($budget)]);
    }

    /**
     * Recalcula los vínculos y devuelve el HTML fresco del panel editado
     * (tarjetas KPI + tabla) y, si tiene contraparte vinculada, también el
     * de la contraparte — necesario porque una edición en el ERI cambia la
     * "Utilidad del período" del ESF (y viceversa con "Depreciaciones"), y
     * ambos paneles conviven en la misma página (pestañas Alpine, sin
     * recargar): sin el HTML de la contraparte, el otro tab se queda con el
     * badge "Cuadra"/"Descuadre" y los totales desactualizados hasta que el
     * usuario recarga manualmente, aunque en base de datos ya esté correcto.
     */
    private function renderStatementBody(Budget $budget): array
    {
        $fresh = Budget::with(['sections.lines.values', 'linkedCounterpart.sections.lines.values'])->find($budget->id);
        $this->projectStatements($fresh);

        $counterpart = null;
        if ($fresh->linkedCounterpart) {
            $counterpart = Budget::with(['sections.lines.values', 'linkedCounterpart.sections.lines.values'])
                ->find($fresh->linkedCounterpart->id);
            $this->projectStatements($counterpart);
        }

        $result = ['html' => $this->renderStatementPanel($budget->id)];

        if ($counterpart) {
            $result['counterpart_id']   = $counterpart->id;
            $result['counterpart_html'] = $this->renderStatementPanel($counterpart->id);
        }

        return $result;
    }

    /**
     * Renderiza el panel completo (tarjetas KPI + tabla) de un ESF o ERI —
     * mismas variables que arma `statementsShow()` para la carga inicial de
     * `financial/statements/show.blade.php`, pero aisladas por estado para
     * poder recalcularlas una a la vez tras una edición puntual de celda.
     */
    private function renderStatementPanel(int $budgetId): string
    {
        $budget = Budget::with('sections.lines.values')->find($budgetId);
        $isEsf  = $budget->type === 'esf';
        $report = $isEsf ? $budget->buildEsfReport() : $budget->buildEriReport();
        $periodLabels = $report['periodLabels'];
        $lastIdx = array_key_last($periodLabels);

        if ($isEsf) {
            return view('financial.statements._esf-panel', [
                'esf'             => $budget,
                'esfReport'       => $report,
                'esfPeriodLabels' => $periodLabels,
                'totalActivo'     => $report['totalActivo'][$lastIdx] ?? 0,
                'totalPasivo'     => $report['totalPasivo'][$lastIdx] ?? 0,
                'totalPatrim'     => $report['totalPatrimonio'][$lastIdx] ?? 0,
                'kpiBalanced'     => abs($report['diferencia'][$lastIdx] ?? 0) < 1,
            ])->render();
        }

        $ventasNetas  = $report['ventasNetas'][$lastIdx] ?? 0;
        $utilidadNeta = $report['utilidadNeta'][$lastIdx] ?? 0;

        return view('financial.statements._eri-panel', [
            'eri'             => $budget,
            'eriReport'       => $report,
            'eriPeriodLabels' => $periodLabels,
            'ventasNetas'     => $ventasNetas,
            'utilidadNeta'    => $utilidadNeta,
            'margen'          => $ventasNetas != 0 ? ($utilidadNeta / $ventasNetas) * 100 : null,
        ])->render();
    }

    // ── Datos (antes "Variables") ───────────────────────────────────────────

    public function data(Request $request, Client $client): View
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $clientData = ClientBudgetData::firstOrNew(
            ['user_id' => $request->user()->id, 'client_id' => $client->id]
        );

        $yearlyRows = ClientBudgetYearlyData::where('client_id', $client->id)->get();
        $years = $yearlyRows->pluck('year')->unique()->sort()->values();
        if ($years->isEmpty()) {
            $years = collect([now()->year, now()->year + 1]);
        }

        $yearly = [];
        foreach (ClientBudgetYearlyData::INDICATORS as $key => $label) {
            foreach ($years as $year) {
                $yearly[$key][$year] = optional($yearlyRows->first(fn ($r) => $r->indicator === $key && $r->year === $year))->value;
            }
        }

        $legalSettings = PayrollLegalSetting::whereDate('effective_from', '<=', now())
            ->orderByDesc('effective_from')->first();

        return view('financial.data', compact('client', 'clientData', 'years', 'yearly', 'legalSettings'));
    }

    public function saveData(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'credit_sales_pct'      => 'required|numeric|min:0|max:100',
            'collection_days'       => 'required|integer|min:0|max:360',
            'supplier_payment_days' => 'required|integer|min:0|max:360',
            'interest_rate'         => 'required|numeric|min:0|max:100',
            'income_tax_rate'       => 'required|numeric|min:0|max:100',
            'legal_reserve_pct'     => 'required|numeric|min:0|max:100',
            'partner_contributions' => 'required|numeric|min:0',
            'years'                 => 'nullable|array',
            'years.*'               => 'integer|min:2000|max:2100',
            'indicators'             => 'nullable|array',
            'indicators.*.*'         => 'nullable|numeric',
        ]);

        ClientBudgetData::updateOrCreate(
            ['user_id' => $request->user()->id, 'client_id' => $client->id],
            [
                'credit_sales_pct'      => $data['credit_sales_pct'],
                'collection_days'       => $data['collection_days'],
                'supplier_payment_days' => $data['supplier_payment_days'],
                'interest_rate'         => $data['interest_rate'],
                'income_tax_rate'       => $data['income_tax_rate'],
                'legal_reserve_pct'     => $data['legal_reserve_pct'],
                'partner_contributions' => $data['partner_contributions'],
            ]
        );

        foreach (($data['indicators'] ?? []) as $indicator => $byYear) {
            if (!array_key_exists($indicator, ClientBudgetYearlyData::INDICATORS)) {
                continue;
            }
            foreach ($byYear as $year => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                ClientBudgetYearlyData::updateOrCreate(
                    ['client_id' => $client->id, 'indicator' => $indicator, 'year' => (int) $year],
                    ['user_id' => $request->user()->id, 'value' => $value]
                );
            }
        }

        return redirect()->route('financial.client', $client)
            ->with('success', 'Datos del cliente actualizados correctamente.');
    }

    /**
     * Niveles óptimos del modal "Ver indicadores financieros" (hoja
     * "Calculadora" del Excel de referencia) — editables por el usuario.
     */
    public function updateRatioTargets(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'ratio_liquidity_target'         => 'required|numeric|min:0',
            'ratio_debt_target'              => 'required|numeric|min:0|max:1',
            'ratio_interest_coverage_target' => 'required|numeric|min:0',
            'ratio_roe_target'               => 'required|numeric|min:0|max:1',
            'ratio_roa_target'               => 'required|numeric|min:0|max:1',
            'ratio_working_capital_target'   => 'required|numeric',
            'redirect_budget_id'             => 'nullable|exists:budgets,id',
        ]);

        ClientBudgetData::updateOrCreate(
            ['user_id' => $request->user()->id, 'client_id' => $client->id],
            collect($data)->except('redirect_budget_id')->all()
        );

        $redirectBudget = !empty($data['redirect_budget_id'])
            ? Budget::where('id', $data['redirect_budget_id'])->where('user_id', $request->user()->id)->first()
            : null;

        $redirectUrl = $redirectBudget
            ? ($this->resolveStatementPair($redirectBudget) ? route('financial.statements.show', $redirectBudget) : route('financial.show', $redirectBudget))
            : route('financial.statements.client', $client);

        return redirect()->to($redirectUrl)
            ->with('success', 'Criterios de indicadores actualizados.')
            ->with('open_ratios_modal', true);
    }

    // ── Motor de proyección ─────────────────────────────────────────────────

    private function projectGeneric(Budget $budget, ClientBudgetData $data): void
    {
        $resolver = new YearlyIndicatorResolver($budget->client_id);

        foreach ($budget->sections as $section) {
            foreach ($section->lines as $line) {
                $baseValue = $line->getValueForPeriod(0);

                for ($i = 1; $i <= $budget->periods_count; $i++) {
                    $existing = $line->values->first(
                        fn (BudgetValue $v) => $v->period_index === $i && $v->value_type === 'budgeted'
                    );
                    if ($existing && $existing->is_manual_override) continue;

                    $projected = $this->calculateProjected($line, $baseValue, $i, $budget, $resolver);

                    BudgetValue::updateOrCreate(
                        ['line_id' => $line->id, 'period_index' => $i, 'value_type' => 'budgeted'],
                        [
                            'budget_id'          => $budget->id,
                            'period_label'       => $budget->buildPeriodLabel($i),
                            'value'              => $projected,
                            'is_manual_override' => false,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Proyección especial para Flujo de Caja (Ppto):
     * - Primera sección (primer rubro) = Saldo Inicial
     * - Última sección (primer rubro)  = Saldo Final
     * - El resto de renglones usa su driver normal (inflación/SMMLV/%/fijo/manual).
     * - "Recaudo de cartera" se deriva aparte, después, de la propia línea
     *   "Ventas" ya proyectada (ver projectRecaudoCartera()).
     * - Luego recalcula la cadena de saldos con recomputeFlujoCajaBalances()
     */
    private function projectFlujoCaja(Budget $budget, ClientBudgetData $data): void
    {
        $sections = $budget->sections;

        if ($sections->count() < 2) {
            $this->projectGeneric($budget, $data);
            return;
        }

        $saldoInicialLine = $sections->first()->lines->first();
        $saldoFinalLine   = $sections->last()->lines->first();

        if (!$saldoInicialLine || !$saldoFinalLine || $saldoInicialLine->id === $saldoFinalLine->id) {
            $this->projectGeneric($budget, $data);
            return;
        }

        $resolver      = new YearlyIndicatorResolver($budget->client_id);
        $recaudoLineId = $this->findLineByName($budget, 'Recaudo de cartera')?->id;

        foreach ($sections as $section) {
            foreach ($section->lines as $line) {
                if ($line->id === $saldoInicialLine->id || $line->id === $saldoFinalLine->id) {
                    continue;
                }
                if ($recaudoLineId && $line->id === $recaudoLineId) {
                    continue; // se deriva después de proyectar "Ventas", ver abajo
                }

                $baseValue = $line->getValueForPeriod(0, 'budgeted');

                for ($i = 1; $i <= $budget->periods_count; $i++) {
                    $existing = $line->values->first(
                        fn (BudgetValue $v) => $v->period_index === $i && $v->value_type === 'budgeted'
                    );
                    if ($existing && $existing->is_manual_override) continue;

                    $projected = $this->calculateProjected($line, $baseValue, $i, $budget, $resolver);

                    BudgetValue::updateOrCreate(
                        ['line_id' => $line->id, 'period_index' => $i, 'value_type' => 'budgeted'],
                        [
                            'budget_id'          => $budget->id,
                            'period_label'       => $budget->buildPeriodLabel($i),
                            'value'              => $projected,
                            'is_manual_override' => false,
                        ]
                    );
                }
            }
        }

        // "Recaudo de cartera" necesita leer "Ventas" ya proyectada arriba —
        // hay que recargar `values` en memoria antes de resolverla.
        $budget->load('sections.lines.values');
        $recaudoLine = $this->findLineByName($budget, 'Recaudo de cartera');
        if ($recaudoLine) {
            $this->projectRecaudoCartera($budget, $recaudoLine, $data);
        }

        $budget->load('sections.lines.values');
        $this->recomputeFlujoCajaBalances($budget, 'budgeted');
    }

    /**
     * "Recaudo de cartera" autocontenido: se deriva de la propia línea
     * "Ventas" del presupuesto (ya proyectada, con su propio driver) en vez
     * de traerse de un presupuesto de Ventas externo. "Ventas" representa
     * las ventas de contado del período (recibidas en efectivo de inmediato);
     * a partir de `credit_sales_pct` (Datos) se reconstruye el total de
     * ventas del período (contado + crédito) y la porción a crédito, que se
     * cobra `collection_days` después.
     */
    private function projectRecaudoCartera(Budget $budget, BudgetLine $recaudoLine, ClientBudgetData $data): void
    {
        // Igual que cualquier otro renglón, sin un valor propio en el año
        // base "Recaudo de cartera" se queda en $0 — el 0 se trata como
        // "todavía sin definir" (mismo criterio que el resto del motor de
        // proyección), así que el auto-cálculo a partir de "Ventas" + la
        // política de cartera de Datos solo se activa si el usuario digitó
        // algo aquí primero.
        if ($recaudoLine->getValueForPeriod(0, 'budgeted') == 0.0) {
            return;
        }

        $ventasLine = $this->findLineByName($budget, 'Ventas');
        if (!$ventasLine) {
            return;
        }

        $creditPct = ($data->credit_sales_pct ?? 60) / 100;
        $cashPct   = 1 - $creditPct;
        if ($cashPct <= 0.0001) {
            return; // 100% a crédito: sin ventas de contado para reconstruir el total del período
        }

        $lagPeriods = max(1, (int) round(($data->collection_days ?? 15) / 30));

        for ($i = 1; $i <= $budget->periods_count; $i++) {
            $existing = $recaudoLine->values->first(
                fn (BudgetValue $v) => $v->period_index === $i && $v->value_type === 'budgeted'
            );
            if ($existing && $existing->is_manual_override) continue;

            $priorPeriod = $i - $lagPeriods;
            $recaudo = 0.0;
            if ($priorPeriod >= 0) {
                $totalSales = $ventasLine->getValueForPeriod($priorPeriod, 'budgeted') / $cashPct;
                $recaudo    = round($totalSales * $creditPct, 2);
            }

            BudgetValue::updateOrCreate(
                ['line_id' => $recaudoLine->id, 'period_index' => $i, 'value_type' => 'budgeted'],
                [
                    'budget_id'          => $budget->id,
                    'period_label'       => $budget->buildPeriodLabel($i),
                    'value'              => $recaudo,
                    'is_manual_override' => false,
                ]
            );
        }
    }

    /**
     * Igual que `matchPeriodIndex()` pero sin caer al período más cercano —
     * null si el presupuesto no tiene un período con ese año exacto. Usado
     * en los vínculos ESF↔ERI (`projectEsfLinks()`/`projectEriLinks()`):
     * ahí "el período más cercano" sería incorrecto, ya que mezclaría la
     * utilidad/depreciación de un año con el rótulo de otro (p. ej. un ERI
     * de un solo año 2026 vinculado a un ESF con 2026 y 2027 no debe volcar
     * la utilidad de 2026 en la columna 2027 del ESF).
     *
     * **Bug real encontrado y corregido**: para periodicidad no anual
     * (mensual/trimestral/cuatrimestral/semestral), varios períodos caen en
     * el MISMO año calendario — emparejar solo por año no los distingue y
     * termina resolviendo siempre el primer período de ese año, sin importar
     * cuál de los $target->periods_count se esté pidiendo. En un ESF/ERI de,
     * p. ej., 3 meses del mismo 2026 esto arrastraba la "Utilidad del
     * período" del Mes 1 a los 3 meses por igual (reportado por el usuario
     * armando un ESF real). Como un ESF/ERI creado como par comparte SIEMPRE
     * la misma periodicidad y cantidad de períodos (configuración
     * compartida desde `statementsStore()`), en ese caso el índice
     * posicional es la correspondencia correcta y no requiere año — se usa
     * directamente. El emparejamiento por año se conserva como respaldo
     * solo para periodicidad anual (donde sí puede haber años no
     * consecutivos) o para un par legado con periodicidades distintas entre
     * sí (vinculado a mano, ver `financialRatiosReport()`).
     */
    private function matchPeriodIndexExact(Budget $target, Budget $source, int $sourceIndex): ?int
    {
        if ($source->period_type !== 'annual'
            && $target->period_type === $source->period_type
            && $target->periods_count === $source->periods_count) {
            return $sourceIndex;
        }

        $year = $source->calendarYearForPeriod($sourceIndex);
        for ($i = 0; $i <= $target->periods_count; $i++) {
            if ($target->calendarYearForPeriod($i) === $year) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Conecta ESF↔ERI cuando están vinculados entre sí
     * (`linked_counterpart_budget_id`). Nunca sobrescribe un valor que el
     * usuario ya marcó manual.
     */
    private function projectStatements(Budget $budget): void
    {
        if ($budget->type === 'esf') {
            $this->projectEsfLinks($budget);
        } elseif ($budget->type === 'eri') {
            $this->projectEriLinks($budget);
        }
    }

    /**
     * ESF: "Utilidad (pérdida) del período" ← utilidad neta del ERI vinculado
     * (emparejado por año calendario, vía `buildEriReport()` — es un
     * subtotal calculado, no un renglón editable, así que no se puede leer
     * con `getValueForPeriod()`). "Resultados acumulados de ejercicios
     * anteriores" se arrastra del propio período anterior (acumulados +
     * utilidad de ese período) — mismo patrón que el saldo de caja de
     * Flujo de Caja, necesario para que un ESF multi-período cuadre.
     */
    private function projectEsfLinks(Budget $budget): void
    {
        $utilidadLine   = $this->findLineByName($budget, Budget::ESF_UTILIDAD_LINE);
        $resultadosLine = $this->findLineByName($budget, Budget::ESF_RESULTADOS_ACUM_LINE);
        $eri = $budget->linkedCounterpart && $budget->linkedCounterpart->type === 'eri' ? $budget->linkedCounterpart : null;

        $utilidadNetaByEriIdx = null;
        if ($eri && $utilidadLine) {
            $eriReport = $eri->buildEriReport();
            $row = $eriReport ? collect($eriReport['rows'])->firstWhere('key', Budget::ERI_UTILIDAD_NETA_KEY) : null;
            $utilidadNetaByEriIdx = $row['values'] ?? null;
        }

        $utilidadResolved   = [];
        $resultadosResolved = [];

        for ($i = 0; $i <= $budget->periods_count; $i++) {
            if ($utilidadLine) {
                if ($this->isManualOverride($utilidadLine, $i)) {
                    $utilidadResolved[$i] = $utilidadLine->getValueForPeriod($i, 'budgeted');
                } elseif ($utilidadNetaByEriIdx !== null && ($eriIdx = $this->matchPeriodIndexExact($eri, $budget, $i)) !== null) {
                    $val = round($utilidadNetaByEriIdx[$eriIdx] ?? 0.0, 2);
                    $this->setLineValue($budget, $utilidadLine, $i, $val);
                    $utilidadResolved[$i] = $val;
                } else {
                    $utilidadResolved[$i] = $utilidadLine->getValueForPeriod($i, 'budgeted');
                }
            }

            if ($resultadosLine) {
                if ($i === 0) {
                    // Período 0 = saldo inicial de acumulados, siempre manual.
                    $resultadosResolved[0] = $resultadosLine->getValueForPeriod(0, 'budgeted');
                    continue;
                }

                if ($this->isManualOverride($resultadosLine, $i)) {
                    $resultadosResolved[$i] = $resultadosLine->getValueForPeriod($i, 'budgeted');
                } else {
                    $val = round(($resultadosResolved[$i - 1] ?? 0.0) + ($utilidadResolved[$i - 1] ?? 0.0), 2);
                    $this->setLineValue($budget, $resultadosLine, $i, $val);
                    $resultadosResolved[$i] = $val;
                }
            }
        }
    }

    /**
     * ERI: "Depreciaciones y amortizaciones" ← delta de "Depreciación
     * acumulada" del ESF vinculado entre el período coincidente (por año
     * calendario) y el anterior — la porción del período, no el saldo
     * completo. Guardada en magnitud positiva (la línea ya está marcada
     * `sign_negative` en el catálogo por defecto).
     */
    private function projectEriLinks(Budget $budget): void
    {
        $gastoLine = $this->findLineByName($budget, Budget::ERI_DEPRECIACION_GASTO_LINE);
        $esf = $budget->linkedCounterpart && $budget->linkedCounterpart->type === 'esf' ? $budget->linkedCounterpart : null;

        if (!$gastoLine || !$esf) {
            return;
        }

        $depreciacionLine = $this->findLineByName($esf, Budget::ESF_DEPRECIACION_LINE);
        if (!$depreciacionLine) {
            return;
        }

        for ($i = 0; $i <= $budget->periods_count; $i++) {
            if ($this->isManualOverride($gastoLine, $i)) {
                continue;
            }

            $esfIdx = $this->matchPeriodIndexExact($esf, $budget, $i);
            if ($esfIdx === null) {
                continue;
            }

            $current = $depreciacionLine->getValueForPeriod($esfIdx, 'budgeted');
            $prior   = $esfIdx > 0 ? $depreciacionLine->getValueForPeriod($esfIdx - 1, 'budgeted') : 0.0;

            $this->setLineValue($budget, $gastoLine, $i, round(abs($current - $prior), 2));
        }
    }

    private function budgetNoun(Budget $budget, bool $capitalized = false): string
    {
        $noun = in_array($budget->type, Budget::ESTADO_FINANCIERO_TYPES, true) ? 'estado financiero' : 'presupuesto';

        return $capitalized ? ucfirst($noun) : $noun;
    }

    private function findLineByName(Budget $budget, string $name): ?BudgetLine
    {
        foreach ($budget->sections as $section) {
            foreach ($section->lines as $line) {
                if ($line->name === $name) {
                    return $line;
                }
            }
        }

        return null;
    }

    /**
     * Indicadores financieros de la hoja "Calculadora" del caso práctico de
     * Actualícese (Documentos/Estados_Financieros.xlsx), calculados en vivo
     * a partir de un ESF y un ERI vinculados entre sí — nada se persiste.
     * Emparejado por año calendario (igual que `projectStatements()`), así
     * que funciona aunque tengan periodicidades distintas. Requiere
     * `sections.lines.values` precargado en ambos.
     */
    private function financialRatiosReport(Budget $esf, Budget $eri, ?ClientBudgetData $targets): array
    {
        $esfReport = $esf->buildEsfReport();
        $eriReport = $eri->buildEriReport();
        if (!$esfReport || !$eriReport) {
            return [];
        }

        $gastosFinancierosLine = $this->findLineByName($eri, Budget::ERI_GASTOS_FINANCIEROS_LINE);

        $targetLiquidity = $targets->ratio_liquidity_target ?? 2.0;
        $targetDebt      = $targets->ratio_debt_target ?? 0.40;
        $targetCoverage  = $targets->ratio_interest_coverage_target ?? 14.0;
        $targetRoe       = $targets->ratio_roe_target ?? 0.14;
        $targetRoa       = $targets->ratio_roa_target ?? 0.14;
        $targetKt        = $targets->ratio_working_capital_target ?? 0.0;

        $rows = [];
        for ($i = 0; $i <= $esf->periods_count; $i++) {
            // Sin período exacto (mismo año calendario) en el ERI vinculado, no
            // hay EBIT/gastos financieros de ese año para calcular — se deja en
            // null (se ve como "—") en vez de mezclar cifras de otro año.
            $eriIdx = $this->matchPeriodIndexExact($eri, $esf, $i);

            $activoCte  = $esfReport['totalActivoCorriente'][$i] ?? 0.0;
            $pasivoCte  = $esfReport['totalPasivoCorriente'][$i] ?? 0.0;
            $activoT    = $esfReport['totalActivo'][$i] ?? 0.0;
            $pasivoT    = $esfReport['totalPasivo'][$i] ?? 0.0;
            $patrimonio = $esfReport['totalPatrimonio'][$i] ?? 0.0;
            $ebit       = $eriIdx !== null ? ($eriReport['ebit'][$eriIdx] ?? 0.0) : null;
            $gastosFin  = $eriIdx !== null ? abs($gastosFinancierosLine?->getValueForPeriod($eriIdx) ?? 0.0) : null;

            $liquidez      = $pasivoCte != 0.0 ? $activoCte / $pasivoCte : null;
            $endeudamiento = $activoT != 0.0 ? $pasivoT / $activoT : null;
            $cobertura     = ($ebit !== null && $gastosFin !== null && $gastosFin != 0.0) ? $ebit / $gastosFin : null;
            $roe           = ($ebit !== null && $patrimonio != 0.0) ? $ebit / $patrimonio : null;
            $roa           = ($ebit !== null && $activoT != 0.0) ? $ebit / $activoT : null;
            $kt            = $activoCte - $pasivoCte;

            $rows[$i] = [
                'label'         => $esf->buildPeriodLabel($i),
                'liquidez'      => ['label' => 'Liquidez',                    'value' => $liquidez,      'target' => $targetLiquidity, 'suffix' => 'x', 'ok' => $liquidez !== null && $liquidez >= $targetLiquidity],
                'endeudamiento' => ['label' => 'Endeudamiento',                'value' => $endeudamiento, 'target' => $targetDebt,      'suffix' => '%', 'ok' => $endeudamiento !== null && $endeudamiento < $targetDebt],
                'cobertura'     => ['label' => 'Cobertura de intereses',      'value' => $cobertura,     'target' => $targetCoverage,  'suffix' => 'x', 'ok' => $cobertura !== null && $cobertura >= $targetCoverage],
                'roe'           => ['label' => 'Rentabilidad del patrimonio', 'value' => $roe,           'target' => $targetRoe,       'suffix' => '%', 'ok' => $roe !== null && $roe >= $targetRoe],
                'roa'           => ['label' => 'Rentabilidad sobre activos',  'value' => $roa,           'target' => $targetRoa,       'suffix' => '%', 'ok' => $roa !== null && $roa >= $targetRoa],
                'kt'            => ['label' => 'Capital de trabajo',          'value' => $kt,            'target' => $targetKt,        'suffix' => '$', 'ok' => $kt >= $targetKt],
            ];
        }

        return $rows;
    }

    private function isManualOverride(BudgetLine $line, int $periodIndex, string $valueType = 'budgeted'): bool
    {
        $existing = $line->values->first(
            fn (BudgetValue $v) => $v->period_index === $periodIndex && $v->value_type === $valueType
        );

        return $existing && $existing->is_manual_override;
    }

    private function setLineValue(Budget $budget, BudgetLine $line, int $periodIndex, float $value): void
    {
        BudgetValue::updateOrCreate(
            ['line_id' => $line->id, 'period_index' => $periodIndex, 'value_type' => 'budgeted'],
            [
                'budget_id'          => $budget->id,
                'period_label'       => $budget->buildPeriodLabel($periodIndex),
                'value'              => $value,
                'is_manual_override' => false,
            ]
        );
    }

    /**
     * Recalcula la cadena de saldos de un flujo de caja para una serie dada
     * (Ppto o Real): Saldo Final[i] = Saldo Inicial[i] + flujo_neto[i], y
     * Saldo Inicial[i+1] = Saldo Final[i]. El flujo neto suma todas las secciones
     * intermedias, restando las marcadas is_outflow=true (p. ej. "Salidas").
     * El saldo inicial del período 0 nunca se toca aquí: es siempre un valor manual.
     */
    private function recomputeFlujoCajaBalances(Budget $budget, string $valueType): void
    {
        $sections = $budget->sections;

        if ($sections->count() < 2) {
            return;
        }

        $saldoInicialLine = $sections->first()->lines->first();
        $saldoFinalLine   = $sections->last()->lines->first();

        if (!$saldoInicialLine || !$saldoFinalLine || $saldoInicialLine->id === $saldoFinalLine->id) {
            return;
        }

        $middleSections = $sections->slice(1, $sections->count() - 2)->values();

        $netFlow = function (int $period) use ($middleSections, $valueType): float {
            $net = 0.0;
            foreach ($middleSections as $section) {
                foreach ($section->lines as $line) {
                    $val = $line->getValueForPeriod($period, $valueType);
                    $net += $section->is_outflow ? -$val : $val;
                }
            }
            return $net;
        };

        $baseInitial    = $saldoInicialLine->getValueForPeriod(0, $valueType);
        $prevFinalValue = round($baseInitial + $netFlow(0), 2);

        BudgetValue::updateOrCreate(
            ['line_id' => $saldoFinalLine->id, 'period_index' => 0, 'value_type' => $valueType],
            [
                'budget_id'          => $budget->id,
                'period_label'       => $budget->buildPeriodLabel(0),
                'value'              => $prevFinalValue,
                'is_manual_override' => false,
            ]
        );

        for ($i = 1; $i <= $budget->periods_count; $i++) {
            BudgetValue::updateOrCreate(
                ['line_id' => $saldoInicialLine->id, 'period_index' => $i, 'value_type' => $valueType],
                [
                    'budget_id'          => $budget->id,
                    'period_label'       => $budget->buildPeriodLabel($i),
                    'value'              => $prevFinalValue,
                    'is_manual_override' => false,
                ]
            );

            $saldoFinal = round($prevFinalValue + $netFlow($i), 2);

            BudgetValue::updateOrCreate(
                ['line_id' => $saldoFinalLine->id, 'period_index' => $i, 'value_type' => $valueType],
                [
                    'budget_id'          => $budget->id,
                    'period_label'       => $budget->buildPeriodLabel($i),
                    'value'              => $saldoFinal,
                    'is_manual_override' => false,
                ]
            );

            $prevFinalValue = $saldoFinal;
        }
    }

    private function calculateProjected(BudgetLine $line, float $baseValue, int $periodIndex, Budget $budget, YearlyIndicatorResolver $resolver): float
    {
        if ($line->projection_driver === 'manual' || $line->projection_driver === 'fixed') {
            return $baseValue;
        }

        if ($line->projection_driver === 'custom_pct') {
            $rate = $line->custom_rate ?? 0;
            return round($baseValue * pow(1 + $rate / 100, $periodIndex), 2);
        }

        $toYear = $budget->calendarYearForPeriod($periodIndex);

        if ($line->projection_driver === 'inflation') {
            return round($baseValue * $resolver->inflationFactor($budget->base_year, $toYear), 2);
        }

        if ($line->projection_driver === 'smmlv') {
            return round($baseValue * $resolver->chainFactor('smmlv', $budget->base_year, $toYear), 2);
        }

        return $baseValue;
    }

    public function defaultSectionsFor(?string $type): array
    {
        return match ($type) {
            'flujo_caja' => $this->defaultFlujoCaja(),
            'esf'        => $this->defaultEsf(),
            'eri'        => $this->defaultEri(),
            default      => [],
        };
    }

    /**
     * Catálogo estándar del flujo de caja real y presupuestado de Actualícese
     * (Documentos/VA24-Flujo-de-caja-real-y-presupuestado.xlsx): saldo inicial,
     * entradas, salidas y saldo final — autocontenido, sin depender de otros
     * presupuestos. Drivers asignados con criterio de CFO para que "Proyectar"
     * dé un punto de partida razonable en vez de repetir el valor del período
     * base sin cambios: "Ventas" crece con una tasa mensual (`custom_pct`,
     * sugerida = inflación de Datos); "Pago de nómina" con el SMMLV; arriendo/
     * servicios públicos/seguros/mantenimiento con inflación. Impuestos,
     * financiación e inversión quedan `manual` (eventos discretos, se digitan
     * mes a mes). "Recaudo de cartera" no lleva driver propio — se deriva de
     * la línea "Ventas" ya proyectada (ver `projectRecaudoCartera()`).
     */
    private function defaultFlujoCaja(): array
    {
        return [
            ['name' => 'Saldo inicial', 'lines' => [
                ['name' => 'Saldo inicial', 'driver' => 'manual'],
            ]],
            ['name' => 'Entradas', 'lines' => [
                ['name' => 'Ventas',                                    'driver' => 'custom_pct'],
                ['name' => 'Recaudo de cartera',                        'driver' => 'manual'],
                ['name' => 'Aportes de socios',                         'driver' => 'manual'],
                ['name' => 'Préstamos bancarios solicitados',           'driver' => 'manual'],
                ['name' => 'Préstamos solicitados a socios o accionistas', 'driver' => 'manual'],
                ['name' => 'Recaudo de préstamos realizados a empleados', 'driver' => 'manual'],
                ['name' => 'Ventas de propiedad, planta y equipo',      'driver' => 'manual'],
                ['name' => 'Venta de activos intangibles',              'driver' => 'manual'],
                ['name' => 'Intereses financieros recibidos',           'driver' => 'manual'],
                ['name' => 'Dividendos y participaciones recibidos',    'driver' => 'manual'],
                ['name' => 'Devolución de impuestos',                   'driver' => 'manual'],
                ['name' => 'Otras entradas',                            'driver' => 'manual'],
            ]],
            ['name' => 'Salidas', 'is_outflow' => true, 'lines' => [
                ['name' => 'Pago a proveedores',                                       'driver' => 'manual'],
                ['name' => 'Pago de servicios públicos',                               'driver' => 'inflation'],
                ['name' => 'Pago de nómina',                                           'driver' => 'smmlv'],
                ['name' => 'Pago por arrendamiento',                                   'driver' => 'inflation'],
                ['name' => 'Pagos por publicidad',                                     'driver' => 'manual'],
                ['name' => 'Pagos por asesorías',                                      'driver' => 'manual'],
                ['name' => 'Pagos por reparaciones y mantenimiento',                   'driver' => 'inflation'],
                ['name' => 'Pagos a otros acreedores',                                 'driver' => 'manual'],
                ['name' => 'Pagos por seguros',                                        'driver' => 'inflation'],
                ['name' => 'Pago de impuesto de renta',                                'driver' => 'manual'],
                ['name' => 'Pago de IVA',                                              'driver' => 'manual'],
                ['name' => 'Pago de impuesto al consumo',                              'driver' => 'manual'],
                ['name' => 'Pago de retención en la fuente',                           'driver' => 'manual'],
                ['name' => 'Pago de cuotas por préstamos bancarios',                   'driver' => 'manual'],
                ['name' => 'Pago de cuotas de préstamos solicitados a socios o accionistas', 'driver' => 'manual'],
                ['name' => 'Préstamos otorgados a empleados',                          'driver' => 'manual'],
                ['name' => 'Pago de dividendos o participaciones a socios o accionistas', 'driver' => 'manual'],
                ['name' => 'Compra de propiedad, planta y equipo',                     'driver' => 'manual'],
                ['name' => 'Compra de intangibles',                                    'driver' => 'manual'],
                ['name' => 'Intereses financieros pagados',                            'driver' => 'manual'],
                ['name' => 'Otros pagos',                                              'driver' => 'manual'],
            ]],
            ['name' => 'Saldo final', 'lines' => [
                ['name' => 'Disponible al final del período', 'driver' => 'manual'],
            ]],
        ];
    }

    /**
     * Catálogo del Estado de Situación Financiera, calcado de
     * `Documentos/Estados_Financieros.xlsx`. Todas las contra-cuentas
     * (provisión de cartera, depreciación acumulada) llevan `sign_negative`.
     * "Utilidad (pérdida) del período" y "Resultados acumulados de
     * ejercicios anteriores" quedan en 0 / manual: `projectEsfLinks()` los
     * calcula al Proyectar si el ESF está vinculado a un ERI.
     */
    private function defaultEsf(): array
    {
        return [
            ['name' => 'Activo Corriente', 'statement_role' => 'activo_corriente', 'lines' => [
                ['name' => 'Efectivo y equivalentes de efectivo',       'driver' => 'manual'],
                ['name' => 'Inversiones temporales',                    'driver' => 'manual'],
                ['name' => 'Cuentas por cobrar clientes',                'driver' => 'manual'],
                ['name' => 'Provisión cartera de dudoso recaudo',        'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Otras cuentas por cobrar',                   'driver' => 'manual'],
                ['name' => 'Inventarios',                                'driver' => 'manual'],
                ['name' => 'Gastos pagados por anticipado',              'driver' => 'manual'],
                ['name' => 'Otros activos corrientes',                   'driver' => 'manual'],
            ]],
            ['name' => 'Activo No Corriente', 'statement_role' => 'activo_no_corriente', 'lines' => [
                ['name' => 'Inversiones en subsidiarias y asociadas',    'driver' => 'manual'],
                ['name' => 'Propiedades, planta y equipo — bruto',       'driver' => 'manual'],
                ['name' => Budget::ESF_DEPRECIACION_LINE,                'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Activos intangibles — neto',                 'driver' => 'manual'],
                ['name' => 'Propiedades de inversión',                   'driver' => 'manual'],
                ['name' => 'Otros activos no corrientes',                'driver' => 'manual'],
            ]],
            ['name' => 'Pasivo Corriente', 'statement_role' => 'pasivo_corriente', 'lines' => [
                ['name' => 'Obligaciones financieras corto plazo',       'driver' => 'manual'],
                ['name' => 'Cuentas por pagar proveedores',              'driver' => 'manual'],
                ['name' => 'Costos y gastos por pagar',                  'driver' => 'manual'],
                ['name' => 'Impuestos por pagar',                        'driver' => 'manual'],
                ['name' => 'Obligaciones laborales',                     'driver' => 'manual'],
                ['name' => 'Ingresos recibidos por anticipado',          'driver' => 'manual'],
                ['name' => 'Otros pasivos corrientes',                   'driver' => 'manual'],
            ]],
            ['name' => 'Pasivo No Corriente', 'statement_role' => 'pasivo_no_corriente', 'lines' => [
                ['name' => 'Obligaciones financieras largo plazo',       'driver' => 'manual'],
                ['name' => 'Pasivos por impuesto diferido',              'driver' => 'manual'],
                ['name' => 'Beneficios a empleados largo plazo',         'driver' => 'manual'],
                ['name' => 'Otros pasivos no corrientes',                'driver' => 'manual'],
            ]],
            ['name' => 'Patrimonio', 'statement_role' => 'patrimonio', 'lines' => [
                ['name' => 'Capital social',                             'driver' => 'manual'],
                ['name' => 'Prima en colocación de acciones',            'driver' => 'manual'],
                ['name' => 'Reserva legal',                              'driver' => 'manual'],
                ['name' => 'Otras reservas',                             'driver' => 'manual'],
                ['name' => 'Revalorización del patrimonio',              'driver' => 'manual'],
                ['name' => Budget::ESF_RESULTADOS_ACUM_LINE,             'driver' => 'manual'],
                ['name' => Budget::ESF_UTILIDAD_LINE,                    'driver' => 'manual'],
                ['name' => 'Otro resultado integral acumulado',          'driver' => 'manual'],
            ]],
        ];
    }

    /**
     * Catálogo del Estado de Resultados, calcado de
     * `Documentos/Estados_Financieros.xlsx`. "Devoluciones y descuentos",
     * costo de ventas, todos los gastos e impuestos llevan `sign_negative`
     * (magnitud positiva + signo visual, en vez de la convención de números
     * negativos digitados del Excel). "Inventario final" no lleva
     * `sign_negative` porque reduce el costo de ventas (se suma). El gasto
     * de depreciación queda en 0 / manual: `projectEriLinks()` lo calcula al
     * Proyectar si el ERI está vinculado a un ESF.
     */
    private function defaultEri(): array
    {
        return [
            ['name' => 'Ingresos Operacionales', 'statement_role' => 'ingresos_operacionales', 'lines' => [
                ['name' => 'Ventas brutas de mercancía',   'driver' => 'manual'],
                ['name' => 'Devoluciones y descuentos',    'driver' => 'manual', 'sign_negative' => true],
            ]],
            ['name' => 'Costo de Ventas', 'statement_role' => 'costo_ventas', 'lines' => [
                ['name' => 'Inventario inicial',            'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Compras netas del período',     'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Inventario final',               'driver' => 'manual'],
            ]],
            ['name' => 'Gastos Operacionales de Administración', 'statement_role' => 'gastos_administracion', 'lines' => [
                ['name' => 'Gastos de personal administrativo', 'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Honorarios',                          'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Arrendamientos',                      'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Seguros',                             'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Servicios públicos',                  'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Mantenimiento y reparaciones',        'driver' => 'manual', 'sign_negative' => true],
                ['name' => Budget::ERI_DEPRECIACION_GASTO_LINE,   'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Gastos de administración varios',     'driver' => 'manual', 'sign_negative' => true],
            ]],
            ['name' => 'Gastos Operacionales de Ventas', 'statement_role' => 'gastos_ventas', 'lines' => [
                ['name' => 'Gastos de personal de ventas',        'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Comisiones y honorarios de ventas',   'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Publicidad y marketing',              'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Transporte y logística',              'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Gastos de ventas varios',             'driver' => 'manual', 'sign_negative' => true],
            ]],
            ['name' => 'Ingresos No Operacionales', 'statement_role' => 'ingresos_no_operacionales', 'lines' => [
                ['name' => 'Ingresos financieros',                'driver' => 'manual'],
                ['name' => 'Rendimientos e inversiones',          'driver' => 'manual'],
                ['name' => 'Otros ingresos no operacionales',     'driver' => 'manual'],
            ]],
            ['name' => 'Gastos No Operacionales', 'statement_role' => 'gastos_no_operacionales', 'lines' => [
                ['name' => 'Gastos financieros (intereses)',      'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Diferencia en cambio',                 'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Otros gastos no operacionales',        'driver' => 'manual', 'sign_negative' => true],
            ]],
            ['name' => 'Provisión para Impuestos', 'statement_role' => 'impuestos', 'lines' => [
                ['name' => 'Impuesto de renta corriente',          'driver' => 'manual', 'sign_negative' => true],
                ['name' => 'Impuesto diferido',                    'driver' => 'manual', 'sign_negative' => true],
            ]],
            ['name' => 'Otro Resultado Integral (ORI)', 'statement_role' => 'ori', 'lines' => [
                ['name' => 'Variación de inversiones disponibles para venta', 'driver' => 'manual'],
                ['name' => 'Diferencia en conversión de operaciones en el extranjero', 'driver' => 'manual'],
            ]],
        ];
    }
}
