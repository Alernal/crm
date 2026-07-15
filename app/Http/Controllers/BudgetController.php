<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetClientVariable;
use App\Models\BudgetLine;
use App\Models\BudgetSection;
use App\Models\BudgetValue;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $clients = $request->user()->clients()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $clientIds = $clients->pluck('id');

        $budgetsByClient = Budget::where('user_id', $request->user()->id)
            ->whereIn('client_id', $clientIds)
            ->get(['id', 'client_id', 'type', 'status', 'name', 'base_year', 'periods_count', 'period_type'])
            ->groupBy('client_id');

        $variablesByClient = BudgetClientVariable::where('user_id', $request->user()->id)
            ->whereIn('client_id', $clientIds)
            ->get()
            ->keyBy('client_id');

        $totalBudgets = $budgetsByClient->flatten()->count();

        return view('financial.index', compact('clients', 'budgetsByClient', 'variablesByClient', 'totalBudgets'));
    }

    public function clientBudgets(Request $request, Client $client): View
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $budgets = Budget::where('user_id', $request->user()->id)
            ->where('client_id', $client->id)
            ->orderByDesc('updated_at')
            ->get();

        $variables = BudgetClientVariable::where('user_id', $request->user()->id)
            ->where('client_id', $client->id)
            ->first();

        $kpis = [
            'total'     => $budgets->count(),
            'draft'     => $budgets->where('status', 'draft')->count(),
            'projected' => $budgets->where('status', 'projected')->count(),
            'final'     => $budgets->where('status', 'final')->count(),
        ];

        return view('financial.client', compact('client', 'budgets', 'variables', 'kpis'));
    }

    public function create(Request $request): View
    {
        $clients       = $request->user()->clients()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $preClientId   = $request->query('client_id');
        $preClient     = $preClientId
            ? $request->user()->clients()->find($preClientId)
            : null;

        $defaultSections = [
            'ventas'     => $this->defaultSectionsFor('ventas'),
            'gastos'     => $this->defaultSectionsFor('gastos'),
            'compras'    => $this->defaultSectionsFor('compras'),
            'flujo_caja' => $this->defaultSectionsFor('flujo_caja'),
            'nomina'     => $this->defaultSectionsFor('nomina'),
        ];

        return view('financial.create', compact('clients', 'preClient', 'defaultSections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id'     => 'required|exists:clients,id',
            'name'          => 'required|string|max:200',
            'type'          => 'required|in:ventas,gastos,compras,flujo_caja,nomina',
            'base_year'     => 'required|integer|min:2000|max:2100',
            'period_type'   => 'required|in:annual,quarterly,monthly',
            'periods_count' => 'required|integer|min:1|max:10',
            'notes'         => 'nullable|string|max:1000',
            'sections'      => 'required|array|min:1',
            'sections.*.name'                       => 'required|string|max:200',
            'sections.*.lines'                      => 'required|array|min:1',
            'sections.*.lines.*.name'               => 'required|string|max:200',
            'sections.*.lines.*.projection_driver'  => 'required|string',
            'sections.*.lines.*.custom_rate'        => 'nullable|numeric',
            'sections.*.lines.*.sign_negative'      => 'nullable|boolean',
            'sections.*.lines.*.base_value'         => 'nullable|numeric',
        ]);

        abort_if(
            !$request->user()->clients()->where('id', $data['client_id'])->exists(),
            403
        );

        $budget = DB::transaction(function () use ($data, $request) {
            $budget = $request->user()->budgets()->create([
                'client_id'     => $data['client_id'],
                'name'          => $data['name'],
                'type'          => $data['type'],
                'base_year'     => $data['base_year'],
                'period_type'   => $data['period_type'],
                'periods_count' => $data['periods_count'],
                'status'        => 'draft',
                'notes'         => $data['notes'] ?? null,
            ]);

            foreach ($data['sections'] as $sIdx => $sData) {
                $section = $budget->sections()->create([
                    'name'       => $sData['name'],
                    'sort_order' => $sIdx,
                ]);

                foreach ($sData['lines'] as $lIdx => $lData) {
                    $line = $section->lines()->create([
                        'budget_id'          => $budget->id,
                        'name'               => $lData['name'],
                        'sort_order'         => $lIdx,
                        'projection_driver'  => $lData['projection_driver'],
                        'custom_rate'        => $lData['custom_rate'] ?? null,
                        'sign_negative'      => !empty($lData['sign_negative']),
                        'is_subtotal'        => false,
                    ]);

                    $baseValue = (float) ($lData['base_value'] ?? 0);
                    BudgetValue::create([
                        'budget_id'    => $budget->id,
                        'line_id'      => $line->id,
                        'period_label' => $budget->buildPeriodLabel(0),
                        'period_index' => 0,
                        'value'        => $baseValue,
                        'is_manual_override' => true,
                    ]);
                }
            }

            return $budget;
        });

        return redirect()->route('financial.show', $budget)
            ->with('success', 'Presupuesto creado. Configura las variables y proyecta los períodos.');
    }

    public function show(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['client', 'sections.lines.values']);

        $periodLabels = $budget->getPeriodLabels();
        $variables    = BudgetClientVariable::where('user_id', $request->user()->id)
                            ->where('client_id', $budget->client_id)
                            ->first();

        return view('financial.show', compact('budget', 'periodLabels', 'variables'));
    }

    public function edit(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['sections.lines.values']);
        $clients = $request->user()->clients()->where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('financial.edit', compact('budget', 'clients'));
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'base_year'     => 'required|integer|min:2000|max:2100',
            'period_type'   => 'required|in:annual,quarterly,monthly',
            'periods_count' => 'required|integer|min:1|max:10',
            'notes'         => 'nullable|string|max:1000',
            'status'        => 'nullable|in:draft,projected,final',
            'sections'      => 'required|array|min:1',
            'sections.*.name'                       => 'required|string|max:200',
            'sections.*.lines'                      => 'required|array|min:1',
            'sections.*.lines.*.name'               => 'required|string|max:200',
            'sections.*.lines.*.projection_driver'  => 'required|string',
            'sections.*.lines.*.custom_rate'        => 'nullable|numeric',
            'sections.*.lines.*.sign_negative'      => 'nullable|boolean',
            'sections.*.lines.*.base_value'         => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($budget, $data) {
            $budget->update([
                'name'          => $data['name'],
                'base_year'     => $data['base_year'],
                'period_type'   => $data['period_type'],
                'periods_count' => $data['periods_count'],
                'notes'         => $data['notes'] ?? null,
                'status'        => $data['status'] ?? $budget->status,
            ]);

            // Rebuild sections and lines
            $budget->lines()->delete();
            $budget->sections()->delete();

            foreach ($data['sections'] as $sIdx => $sData) {
                $section = $budget->sections()->create([
                    'name'       => $sData['name'],
                    'sort_order' => $sIdx,
                ]);

                foreach ($sData['lines'] as $lIdx => $lData) {
                    $line = $section->lines()->create([
                        'budget_id'         => $budget->id,
                        'name'              => $lData['name'],
                        'sort_order'        => $lIdx,
                        'projection_driver' => $lData['projection_driver'],
                        'custom_rate'       => $lData['custom_rate'] ?? null,
                        'sign_negative'     => !empty($lData['sign_negative']),
                        'is_subtotal'       => false,
                    ]);

                    BudgetValue::create([
                        'budget_id'          => $budget->id,
                        'line_id'            => $line->id,
                        'period_label'       => $budget->buildPeriodLabel(0),
                        'period_index'       => 0,
                        'value'              => (float) ($lData['base_value'] ?? 0),
                        'is_manual_override' => true,
                    ]);
                }
            }
        });

        return redirect()->route('financial.show', $budget)
            ->with('success', 'Presupuesto actualizado.');
    }

    public function printView(Request $request, Budget $budget): View
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['client', 'sections.lines.values']);
        $user         = $request->user();
        $periodLabels = $budget->getPeriodLabels();
        $orientation  = $this->orientationFor($periodLabels);
        $paperSize    = $orientation === 'portrait' ? 'letter' : 'legal';

        return view('financial.print', compact('budget', 'user', 'periodLabels', 'orientation', 'paperSize'));
    }

    public function pdf(Request $request, Budget $budget): Response
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $budget->load(['client', 'sections.lines.values']);
        $user         = $request->user();
        $periodLabels = $budget->getPeriodLabels();
        $orientation  = $this->orientationFor($periodLabels);
        $paperSize    = $orientation === 'portrait' ? 'letter' : 'legal';

        $pdf = Pdf::loadView('financial.pdf', compact('budget', 'user', 'periodLabels', 'orientation'))
            ->setPaper($paperSize, $orientation);

        $fileSlug = \Illuminate\Support\Str::slug("presupuesto-{$budget->client->name}-{$budget->name}");

        return $pdf->download("{$fileSlug}.pdf");
    }

    /**
     * Vertical para presupuestos con pocos periodos, horizontal cuando la
     * tabla tiene demasiadas columnas para caber legible en una hoja vertical.
     */
    private function orientationFor(array $periodLabels): string
    {
        return count($periodLabels) <= 5 ? 'portrait' : 'landscape';
    }

    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);
        $clientId = $budget->client_id;
        $budget->delete();

        return redirect()->route('financial.client', $clientId)
            ->with('success', 'Presupuesto eliminado.');
    }

    public function project(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $variables = BudgetClientVariable::firstOrCreate(
            ['user_id' => $request->user()->id, 'client_id' => $budget->client_id],
            ['user_id' => $request->user()->id, 'client_id' => $budget->client_id]
        );

        $budget->load('sections.lines.values');

        DB::transaction(function () use ($budget, $variables) {
            if ($budget->type === 'flujo_caja') {
                $this->projectFlujoCaja($budget, $variables);
            } else {
                $this->projectGeneric($budget, $variables);
            }

            $budget->update(['status' => 'projected']);
        });

        return redirect()->route('financial.show', $budget)
            ->with('success', 'Presupuesto proyectado correctamente.');
    }

    public function updateValue(Request $request, Budget $budget): \Illuminate\Http\JsonResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'line_id'      => 'required|exists:budget_lines,id',
            'period_index' => 'required|integer|min:0',
            'value'        => 'required|numeric',
        ]);

        $line = BudgetLine::where('id', $data['line_id'])->where('budget_id', $budget->id)->firstOrFail();

        BudgetValue::updateOrCreate(
            ['line_id' => $line->id, 'period_index' => $data['period_index']],
            [
                'budget_id'          => $budget->id,
                'period_label'       => $budget->buildPeriodLabel($data['period_index']),
                'value'              => $data['value'],
                'is_manual_override' => true,
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function variables(Request $request, Client $client): View
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $vars = BudgetClientVariable::firstOrNew(
            ['user_id' => $request->user()->id, 'client_id' => $client->id]
        );

        return view('financial.variables', compact('client', 'vars'));
    }

    public function saveVariables(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'ipc'                  => 'required|numeric|min:0|max:100',
            'inflation'            => 'required|numeric|min:0|max:100',
            'smmlv_increase'       => 'required|numeric|min:0|max:100',
            'sales_growth'         => 'required|numeric|min:0|max:100',
            'sales_growth_monthly' => 'required|numeric|min:0|max:100',
            'new_clients_pct'      => 'required|numeric|min:0|max:100',
            'payroll_growth'       => 'required|numeric|min:0|max:100',
            'rent_growth'          => 'required|numeric|min:0|max:100',
            'utilities_growth'     => 'required|numeric|min:0|max:100',
            'purchases_growth'     => 'required|numeric|min:0|max:100',
            'interest_rate'        => 'required|numeric|min:0|max:100',
            'services_growth'      => 'required|numeric|min:0|max:100',
        ]);

        BudgetClientVariable::updateOrCreate(
            ['user_id' => $request->user()->id, 'client_id' => $client->id],
            array_merge($data, ['user_id' => $request->user()->id, 'client_id' => $client->id])
        );

        return redirect()->route('financial.client', $client)
            ->with('success', 'Variables macroeconómicas actualizadas correctamente.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function projectGeneric(Budget $budget, BudgetClientVariable $variables): void
    {
        foreach ($budget->sections as $section) {
            foreach ($section->lines as $line) {
                $baseValue = $line->getValueForPeriod(0);

                for ($i = 1; $i <= $budget->periods_count; $i++) {
                    $existing = $line->values->firstWhere('period_index', $i);
                    if ($existing && $existing->is_manual_override) continue;

                    $projected = $this->calculateProjected($line, $baseValue, $i, $variables);

                    BudgetValue::updateOrCreate(
                        ['line_id' => $line->id, 'period_index' => $i],
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
     * Proyección especial para Flujo de Caja:
     * - Primera sección (primer rubro) = Saldo Inicial
     * - Última sección (primer rubro)  = Saldo Final
     * - Secciones "Egreso*" se restan; el resto se suma
     * - Saldo Final[i] = Saldo Inicial[i] + flujo_neto[i]
     * - Saldo Inicial[i+1] = Saldo Final[i]
     */
    private function projectFlujoCaja(Budget $budget, BudgetClientVariable $variables): void
    {
        $sections = $budget->sections;

        if ($sections->count() < 2) {
            $this->projectGeneric($budget, $variables);
            return;
        }

        $saldoInicialLine = $sections->first()->lines->first();
        $saldoFinalLine   = $sections->last()->lines->first();

        if (!$saldoInicialLine || !$saldoFinalLine || $saldoInicialLine->id === $saldoFinalLine->id) {
            $this->projectGeneric($budget, $variables);
            return;
        }

        // Paso 1: proyectar todas las líneas intermedias (no saldo inicial ni saldo final)
        foreach ($sections as $section) {
            foreach ($section->lines as $line) {
                if ($line->id === $saldoInicialLine->id || $line->id === $saldoFinalLine->id) {
                    continue;
                }

                $baseValue = $line->getValueForPeriod(0);

                for ($i = 1; $i <= $budget->periods_count; $i++) {
                    $existing = $line->values->firstWhere('period_index', $i);
                    if ($existing && $existing->is_manual_override) continue;

                    $projected = $this->calculateProjected($line, $baseValue, $i, $variables);

                    BudgetValue::updateOrCreate(
                        ['line_id' => $line->id, 'period_index' => $i],
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

        // Recargar los valores proyectados antes de calcular saldos
        $budget->load('sections.lines.values');
        $sections         = $budget->sections;
        $saldoInicialLine = $sections->first()->lines->first();
        $saldoFinalLine   = $sections->last()->lines->first();
        $middleSections   = $sections->slice(1, $sections->count() - 2)->values();

        // Calcula flujo neto de las secciones intermedias para un período dado.
        // Secciones cuyo nombre contiene "Egreso" se restan; el resto se suma.
        $netFlow = function (int $period) use ($middleSections): float {
            $net = 0.0;
            foreach ($middleSections as $section) {
                $isEgreso = str_contains(strtolower($section->name), 'egreso');
                foreach ($section->lines as $line) {
                    $val = $line->getValueForPeriod($period);
                    $net += $isEgreso ? -$val : $val;
                }
            }
            return $net;
        };

        // Paso 2: calcular y guardar Saldo Final del año base (período 0)
        $baseInitial    = $saldoInicialLine->getValueForPeriod(0);
        $prevFinalValue = round($baseInitial + $netFlow(0), 2);

        BudgetValue::updateOrCreate(
            ['line_id' => $saldoFinalLine->id, 'period_index' => 0],
            [
                'budget_id'          => $budget->id,
                'period_label'       => $budget->buildPeriodLabel(0),
                'value'              => $prevFinalValue,
                'is_manual_override' => false,
            ]
        );

        // Paso 3: período a período — llevar saldo final → saldo inicial siguiente
        for ($i = 1; $i <= $budget->periods_count; $i++) {
            // Saldo Inicial[i] = Saldo Final[i-1]
            BudgetValue::updateOrCreate(
                ['line_id' => $saldoInicialLine->id, 'period_index' => $i],
                [
                    'budget_id'          => $budget->id,
                    'period_label'       => $budget->buildPeriodLabel($i),
                    'value'              => $prevFinalValue,
                    'is_manual_override' => false,
                ]
            );

            // Saldo Final[i] = Saldo Inicial[i] + flujo neto[i]
            $saldoFinal = round($prevFinalValue + $netFlow($i), 2);

            BudgetValue::updateOrCreate(
                ['line_id' => $saldoFinalLine->id, 'period_index' => $i],
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

    private function calculateProjected(BudgetLine $line, float $baseValue, int $periodIndex, BudgetClientVariable $vars): float
    {
        if ($line->projection_driver === 'manual' || $line->projection_driver === 'fixed') {
            return $baseValue;
        }

        $rate = $line->projection_driver === 'custom_pct'
            ? ($line->custom_rate ?? 0)
            : $vars->getRateByDriver($line->projection_driver);

        // Compound growth: base × (1 + rate/100)^n
        return round($baseValue * pow(1 + $rate / 100, $periodIndex), 2);
    }

    public function defaultSectionsFor(?string $type): array
    {
        return match ($type) {
            'ventas'     => $this->defaultVentas(),
            'gastos'     => $this->defaultGastos(),
            'compras'    => $this->defaultCompras(),
            'flujo_caja' => $this->defaultFlujoCaja(),
            'nomina'     => $this->defaultNomina(),
            default      => [],
        };
    }

    private function defaultVentas(): array
    {
        return [
            ['name' => 'Ingresos Operacionales', 'lines' => [
                ['name' => 'Servicios contables y de revisión', 'driver' => 'sales_growth'],
                ['name' => 'Declaraciones tributarias',         'driver' => 'sales_growth'],
                ['name' => 'Consultoría tributaria y fiscal',   'driver' => 'sales_growth'],
                ['name' => 'Revisoría fiscal',                  'driver' => 'sales_growth'],
                ['name' => 'Outsourcing nómina y RRHH',         'driver' => 'sales_growth'],
                ['name' => 'Auditoría de estados financieros',  'driver' => 'sales_growth'],
                ['name' => 'Legalización de empresas',          'driver' => 'sales_growth'],
                ['name' => 'Otros servicios profesionales',     'driver' => 'sales_growth'],
            ]],
            ['name' => 'Ingresos por Nuevos Clientes', 'lines' => [
                ['name' => 'Nuevos clientes proyectados',       'driver' => 'sales_growth'],
                ['name' => 'Ingreso promedio por nuevo cliente','driver' => 'sales_growth'],
            ]],
            ['name' => 'Ingresos No Operacionales', 'lines' => [
                ['name' => 'Intereses ganados',                 'driver' => 'interest_rate'],
                ['name' => 'Descuentos financieros recibidos',  'driver' => 'ipc'],
                ['name' => 'Otros ingresos no operacionales',   'driver' => 'ipc'],
            ]],
        ];
    }

    private function defaultGastos(): array
    {
        return [
            ['name' => 'Gastos de Personal', 'lines' => [
                ['name' => 'Salarios básicos',                  'driver' => 'smmlv'],
                ['name' => 'Horas extras y recargos',           'driver' => 'smmlv'],
                ['name' => 'Bonificaciones y comisiones',       'driver' => 'payroll_growth'],
                ['name' => 'Auxilio de transporte',             'driver' => 'smmlv'],
                ['name' => 'Salud empleador (8.5%)',            'driver' => 'smmlv'],
                ['name' => 'Pensión empleador (12%)',           'driver' => 'smmlv'],
                ['name' => 'ARL',                               'driver' => 'smmlv'],
                ['name' => 'Caja de compensación (4%)',         'driver' => 'smmlv'],
                ['name' => 'SENA (2%)',                         'driver' => 'smmlv'],
                ['name' => 'ICBF (3%)',                         'driver' => 'smmlv'],
                ['name' => 'Cesantías (8.33%)',                 'driver' => 'smmlv'],
                ['name' => 'Intereses sobre cesantías (1%)',    'driver' => 'smmlv'],
                ['name' => 'Prima de servicios (8.33%)',        'driver' => 'smmlv'],
                ['name' => 'Vacaciones (4.17%)',                'driver' => 'smmlv'],
            ]],
            ['name' => 'Gastos Generales de Operación', 'lines' => [
                ['name' => 'Arrendamiento oficina',             'driver' => 'rent_growth'],
                ['name' => 'Servicio de energía eléctrica',     'driver' => 'utilities_growth'],
                ['name' => 'Agua y acueducto',                  'driver' => 'utilities_growth'],
                ['name' => 'Internet y telefonía',              'driver' => 'utilities_growth'],
                ['name' => 'Gas domiciliario',                  'driver' => 'utilities_growth'],
                ['name' => 'Papelería y útiles de oficina',     'driver' => 'ipc'],
                ['name' => 'Cafetería y aseo',                  'driver' => 'ipc'],
                ['name' => 'Aseo y limpieza',                   'driver' => 'ipc'],
                ['name' => 'Transporte y viáticos',             'driver' => 'ipc'],
                ['name' => 'Publicidad y mercadeo',             'driver' => 'sales_growth'],
            ]],
            ['name' => 'Gastos de Tecnología', 'lines' => [
                ['name' => 'Software contable y licencias',     'driver' => 'ipc'],
                ['name' => 'Mantenimiento equipos de cómputo',  'driver' => 'ipc'],
                ['name' => 'Hosting y servicios en la nube',    'driver' => 'ipc'],
                ['name' => 'Otros gastos de tecnología',        'driver' => 'ipc'],
            ]],
            ['name' => 'Gastos Financieros', 'lines' => [
                ['name' => 'Comisiones bancarias',              'driver' => 'ipc'],
                ['name' => 'Intereses sobre créditos',          'driver' => 'interest_rate'],
                ['name' => 'GMF – 4x1000',                      'driver' => 'sales_growth'],
            ]],
            ['name' => 'Otros Gastos', 'lines' => [
                ['name' => 'Seguros',                           'driver' => 'ipc'],
                ['name' => 'Honorarios profesionales externos', 'driver' => 'services_growth'],
                ['name' => 'Capacitación y actualización',      'driver' => 'ipc'],
                ['name' => 'Gastos legales y notariales',       'driver' => 'ipc'],
                ['name' => 'Gastos diversos',                   'driver' => 'ipc'],
            ]],
        ];
    }

    private function defaultCompras(): array
    {
        return [
            ['name' => 'Compras de Mercancías / Insumos', 'lines' => [
                ['name' => 'Compras brutas de mercancías',      'driver' => 'purchases_growth'],
                ['name' => 'Compras de materias primas',        'driver' => 'purchases_growth'],
                ['name' => 'Compras de materiales de empaque',  'driver' => 'purchases_growth'],
                ['name' => 'Devoluciones en compras',           'driver' => 'purchases_growth', 'sign' => true],
                ['name' => 'Descuentos comerciales recibidos',  'driver' => 'ipc', 'sign' => true],
            ]],
            ['name' => 'Costos de Importación', 'lines' => [
                ['name' => 'Fletes y transporte',               'driver' => 'ipc'],
                ['name' => 'Seguros sobre mercancías',          'driver' => 'ipc'],
                ['name' => 'Derechos de aduana e IVA importado','driver' => 'ipc'],
                ['name' => 'Gastos de agenciamiento aduanero',  'driver' => 'ipc'],
            ]],
            ['name' => 'Control de Inventarios', 'lines' => [
                ['name' => 'Inventario inicial del período',    'driver' => 'purchases_growth'],
                ['name' => 'Inventario final del período',      'driver' => 'purchases_growth', 'sign' => true],
                ['name' => 'Costo de ventas (calculado)',       'driver' => 'purchases_growth'],
            ]],
            ['name' => 'Proveedores Principales', 'lines' => [
                ['name' => 'Proveedor 1',                       'driver' => 'purchases_growth'],
                ['name' => 'Proveedor 2',                       'driver' => 'purchases_growth'],
                ['name' => 'Proveedor 3',                       'driver' => 'purchases_growth'],
                ['name' => 'Otros proveedores',                 'driver' => 'purchases_growth'],
            ]],
        ];
    }

    private function defaultFlujoCaja(): array
    {
        return [
            ['name' => 'Saldo Inicial', 'lines' => [
                ['name' => 'Saldo inicial de caja y bancos',    'driver' => 'fixed'],
            ]],
            ['name' => 'Ingresos de Efectivo', 'lines' => [
                ['name' => 'Cobros a clientes (cartera)',        'driver' => 'sales_growth'],
                ['name' => 'Ventas de contado',                  'driver' => 'sales_growth'],
                ['name' => 'Préstamos bancarios recibidos',      'driver' => 'interest_rate'],
                ['name' => 'Aportes de socios / capital',        'driver' => 'fixed'],
                ['name' => 'Rendimientos financieros',           'driver' => 'interest_rate'],
                ['name' => 'Otros ingresos de efectivo',         'driver' => 'ipc'],
            ]],
            ['name' => 'Egresos Operativos', 'lines' => [
                ['name' => 'Pago a proveedores',                 'driver' => 'purchases_growth'],
                ['name' => 'Pago de nómina',                     'driver' => 'smmlv'],
                ['name' => 'Pago de prestaciones sociales',      'driver' => 'smmlv'],
                ['name' => 'Pago parafiscales y seguridad social','driver' => 'smmlv'],
                ['name' => 'Pago de arrendamientos',             'driver' => 'rent_growth'],
                ['name' => 'Pago servicios públicos',            'driver' => 'utilities_growth'],
                ['name' => 'Papelería y gastos menores',         'driver' => 'ipc'],
            ]],
            ['name' => 'Egresos Tributarios', 'lines' => [
                ['name' => 'Pago IVA',                           'driver' => 'sales_growth'],
                ['name' => 'Pago retefuente',                    'driver' => 'sales_growth'],
                ['name' => 'Pago renta / CREE',                  'driver' => 'sales_growth'],
                ['name' => 'Pago ICA',                           'driver' => 'sales_growth'],
                ['name' => 'Otros impuestos y contribuciones',   'driver' => 'ipc'],
            ]],
            ['name' => 'Egresos Financieros', 'lines' => [
                ['name' => 'Pago cuotas de crédito (capital)',   'driver' => 'fixed'],
                ['name' => 'Pago intereses de créditos',         'driver' => 'interest_rate'],
                ['name' => 'GMF y comisiones bancarias',         'driver' => 'sales_growth'],
            ]],
            ['name' => 'Saldo Final', 'lines' => [
                ['name' => 'Saldo final de caja y bancos',       'driver' => 'fixed'],
            ]],
        ];
    }

    private function defaultNomina(): array
    {
        return [
            ['name' => 'Salarios y Compensaciones', 'lines' => [
                ['name' => 'Gerente / Director',                 'driver' => 'smmlv'],
                ['name' => 'Contador principal',                 'driver' => 'smmlv'],
                ['name' => 'Auxiliar contable 1',                'driver' => 'smmlv'],
                ['name' => 'Auxiliar contable 2',                'driver' => 'smmlv'],
                ['name' => 'Asistente administrativo',           'driver' => 'smmlv'],
                ['name' => 'Otros cargos',                       'driver' => 'smmlv'],
                ['name' => 'Horas extras y recargos',            'driver' => 'smmlv'],
                ['name' => 'Bonificaciones',                     'driver' => 'payroll_growth'],
                ['name' => 'Auxilio de transporte',              'driver' => 'smmlv'],
            ]],
            ['name' => 'Aportes Parafiscales Empleador', 'lines' => [
                ['name' => 'Salud empleador (8.5%)',             'driver' => 'smmlv'],
                ['name' => 'Pensión empleador (12%)',            'driver' => 'smmlv'],
                ['name' => 'ARL',                                'driver' => 'smmlv'],
                ['name' => 'Caja de compensación familiar (4%)','driver' => 'smmlv'],
                ['name' => 'SENA (2%)',                          'driver' => 'smmlv'],
                ['name' => 'ICBF (3%)',                          'driver' => 'smmlv'],
            ]],
            ['name' => 'Prestaciones Sociales', 'lines' => [
                ['name' => 'Cesantías (8.33%)',                  'driver' => 'smmlv'],
                ['name' => 'Intereses sobre cesantías (1%)',     'driver' => 'smmlv'],
                ['name' => 'Prima de servicios (8.33%)',         'driver' => 'smmlv'],
                ['name' => 'Vacaciones (4.17%)',                 'driver' => 'smmlv'],
            ]],
            ['name' => 'Deducciones Empleado', 'lines' => [
                ['name' => 'Salud empleado (4%)',                'driver' => 'smmlv'],
                ['name' => 'Pensión empleado (4%)',              'driver' => 'smmlv'],
                ['name' => 'Retención en la fuente salarios',    'driver' => 'smmlv'],
                ['name' => 'Libranzas y descuentos varios',      'driver' => 'fixed'],
            ]],
            ['name' => 'Costo Total de Nómina', 'lines' => [
                ['name' => 'Costo total empleador (SMLV × 2.1)', 'driver' => 'smmlv'],
                ['name' => 'Neto pagado a empleados',            'driver' => 'smmlv'],
            ]],
        ];
    }
}
