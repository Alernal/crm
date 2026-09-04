<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStatementsSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function esfSections(): array
    {
        return [
            ['name' => 'Activo Corriente', 'statement_role' => 'activo_corriente', 'lines' => [
                ['name' => 'Efectivo y equivalentes de efectivo', 'projection_driver' => 'manual', 'values' => ['0' => 100, '1' => 120]],
                ['name' => 'Cuentas por cobrar clientes', 'projection_driver' => 'manual', 'values' => ['0' => 50, '1' => 60]],
            ]],
            ['name' => 'Activo No Corriente', 'statement_role' => 'activo_no_corriente', 'lines' => [
                ['name' => 'Propiedades, planta y equipo — bruto', 'projection_driver' => 'manual', 'values' => ['0' => 200, '1' => 200]],
                ['name' => Budget::ESF_DEPRECIACION_LINE, 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 50, '1' => 70]],
            ]],
            ['name' => 'Pasivo Corriente', 'statement_role' => 'pasivo_corriente', 'lines' => [
                ['name' => 'Cuentas por pagar proveedores', 'projection_driver' => 'manual', 'values' => ['0' => 80, '1' => 90]],
            ]],
            ['name' => 'Pasivo No Corriente', 'statement_role' => 'pasivo_no_corriente', 'lines' => [
                ['name' => 'Obligaciones financieras largo plazo', 'projection_driver' => 'manual', 'values' => ['0' => 70, '1' => 60]],
            ]],
            ['name' => 'Patrimonio', 'statement_role' => 'patrimonio', 'lines' => [
                ['name' => 'Capital social', 'projection_driver' => 'manual', 'values' => ['0' => 150, '1' => 150]],
                ['name' => Budget::ESF_RESULTADOS_ACUM_LINE, 'projection_driver' => 'manual', 'values' => ['0' => 0, '1' => 0]],
                ['name' => Budget::ESF_UTILIDAD_LINE, 'projection_driver' => 'manual', 'values' => ['0' => 0, '1' => 10]],
            ]],
        ];
    }

    private function eriSections(): array
    {
        return [
            ['name' => 'Ingresos Operacionales', 'statement_role' => 'ingresos_operacionales', 'lines' => [
                ['name' => 'Ventas brutas de mercancía', 'projection_driver' => 'manual', 'values' => ['0' => 500, '1' => 500]],
                ['name' => 'Devoluciones y descuentos', 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 20, '1' => 20]],
            ]],
            ['name' => 'Costo de Ventas', 'statement_role' => 'costo_ventas', 'lines' => [
                ['name' => 'Inventario inicial', 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 30, '1' => 30]],
                ['name' => 'Compras netas del período', 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 300, '1' => 300]],
                ['name' => 'Inventario final', 'projection_driver' => 'manual', 'values' => ['0' => 50, '1' => 50]],
            ]],
            ['name' => 'Gastos Operacionales de Administración', 'statement_role' => 'gastos_administracion', 'lines' => [
                ['name' => 'Gastos de personal administrativo', 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 60, '1' => 60]],
                ['name' => Budget::ERI_DEPRECIACION_GASTO_LINE, 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 15, '1' => 15]],
            ]],
            ['name' => 'Gastos Operacionales de Ventas', 'statement_role' => 'gastos_ventas', 'lines' => [
                ['name' => 'Comisiones y honorarios de ventas', 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 40, '1' => 40]],
            ]],
            ['name' => 'Gastos No Operacionales', 'statement_role' => 'gastos_no_operacionales', 'lines' => [
                [
                    'name' => Budget::ERI_GASTOS_FINANCIEROS_LINE, 'projection_driver' => 'manual', 'sign_negative' => '1',
                    'values' => ['0' => 20, '1' => 20],
                ],
            ]],
            ['name' => 'Provisión para Impuestos', 'statement_role' => 'impuestos', 'lines' => [
                ['name' => 'Impuesto de renta corriente', 'projection_driver' => 'manual', 'sign_negative' => '1', 'values' => ['0' => 20, '1' => 20]],
            ]],
        ];
    }

    private function createStatement(User $user, Client $client, string $type, array $sections): Budget
    {
        $route = $type === 'esf' ? 'financial.statements.create' : 'financial.statements.create';
        $this->actingAs($user)->get(route($route, ['client_id' => $client->id]))->assertOk();

        $this->actingAs($user)->post(route('financial.store'), [
            'client_id'     => $client->id,
            'name'          => strtoupper($type) . ' de prueba',
            'type'          => $type,
            'base_year'     => 2026,
            'period_type'   => 'annual',
            'periods_count' => 1,
            'sections'      => $sections,
        ])->assertRedirect();

        return Budget::where('user_id', $user->id)->where('type', $type)->latest('id')->firstOrFail();
    }

    public function test_presupuestos_and_estados_financieros_are_separate_submenus(): void
    {
        $user = User::factory()->create();
        $clientA = Client::create(['user_id' => $user->id, 'name' => 'Cliente Ventas', 'document_number' => '900111001', 'status' => 'active']);
        $clientB = Client::create(['user_id' => $user->id, 'name' => 'Cliente Estados', 'document_number' => '900111002', 'status' => 'active']);

        $this->createStatement($user, $clientB, 'esf', $this->esfSections());

        $indexResponse = $this->actingAs($user)->get(route('financial.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Sin presupuestos aún');

        $statementsIndex = $this->actingAs($user)->get(route('financial.statements.index'));
        $statementsIndex->assertOk();
        $statementsIndex->assertSee('1 estado financiero');
        $statementsIndex->assertSee('Sin estados financieros aún');

        $statementsClient = $this->actingAs($user)->get(route('financial.statements.client', $clientB));
        $statementsClient->assertOk();
        $statementsClient->assertDontSee('Orden recomendado', false);
    }

    public function test_esf_and_eri_manual_grid_persists_and_balances(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas', 'document_number' => '900222001', 'status' => 'active']);

        $esf = $this->createStatement($user, $client, 'esf', $this->esfSections());
        $eri = $this->createStatement($user, $client, 'eri', $this->eriSections());

        $esf->load('sections.lines.values');
        $report = $esf->buildEsfReport();
        $this->assertNotNull($report);
        foreach ($report['diferencia'] as $d) {
            $this->assertLessThan(1, abs($d), 'ESF debe cuadrar Activo = Pasivo + Patrimonio en cada período');
        }
        $this->assertEqualsWithDelta(300.0, $report['totalActivo'][0], 0.01);
        $this->assertEqualsWithDelta(310.0, $report['totalActivo'][1], 0.01);

        $eri->load('sections.lines.values');
        $eriReport = $eri->buildEriReport();
        $this->assertEqualsWithDelta(85.0, $eriReport['ebit'][0], 0.01);
        $this->assertEqualsWithDelta(100.0, $eriReport['ebitda'][0], 0.01);
        $this->assertEqualsWithDelta(45.0, $eriReport['utilidadNeta'][0], 0.01);

        $this->actingAs($user)->put(route('financial.update', $esf), $this->updatePayload($esf, ['linked_counterpart_budget_id' => $eri->id]))
            ->assertRedirect();
        $this->actingAs($user)->put(route('financial.update', $eri), $this->updatePayload($eri, ['linked_counterpart_budget_id' => $esf->id]))
            ->assertRedirect();

        $showEsf = $this->actingAs($user)->get(route('financial.show', $esf->fresh()));
        $showEsf->assertOk();
        $showEsf->assertSee('El balance cuadra en todos los períodos');
        $showEsf->assertSee('Actualizar vínculos');
        $showEsf->assertDontSee('Datos');
        $showEsf->assertSee('Indicadores financieros');
        $showEsf->assertSee('No cumple');
    }

    public function test_ratio_target_editing_changes_compliance_badge(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 2', 'document_number' => '900222002', 'status' => 'active']);

        $esf = $this->createStatement($user, $client, 'esf', $this->esfSections());
        $eri = $this->createStatement($user, $client, 'eri', $this->eriSections());

        $this->actingAs($user)->put(route('financial.update', $esf), $this->updatePayload($esf, ['linked_counterpart_budget_id' => $eri->id]));

        // Una vez vinculados (aunque se hayan creado por separado con el flujo
        // individual heredado), quedan bajo la pantalla combinada nueva.
        // Liquidez período 0 = 150/80 = 1.875, por debajo del óptimo por defecto (2) => "No cumple"
        $before = $this->actingAs($user)->get(route('financial.statements.show', $esf->fresh()));
        $before->assertSee('No cumple');

        $this->actingAs($user)->patch(route('financial.ratio_targets.update', $client), [
            'ratio_liquidity_target'         => 1,
            'ratio_debt_target'              => 0.40,
            'ratio_interest_coverage_target' => 14,
            'ratio_roe_target'               => 0.14,
            'ratio_roa_target'               => 0.14,
            'ratio_working_capital_target'   => 0,
            'redirect_budget_id'             => $esf->id,
        ])->assertRedirect(route('financial.statements.show', $esf));

        $after = $this->actingAs($user)->get(route('financial.statements.show', $esf->fresh()));
        $after->assertOk();
        $after->assertSee('Cumple');
    }

    public function test_edit_prefills_existing_period_values_in_grid(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 3', 'document_number' => '900222003', 'status' => 'active']);

        $esf = $this->createStatement($user, $client, 'esf', $this->esfSections());

        $edit = $this->actingAs($user)->get(route('financial.edit', $esf));
        $edit->assertOk();
        $edit->assertSee('"values":[100,120]', false);
        $edit->assertSee('"values":[50,70]', false);
    }

    public function test_print_and_pdf_signature_block_only_for_statements(): void
    {
        $user = User::factory()->create(['nit' => '900999888-1', 'professional_card_number' => 'TP-12345']);
        $client = Client::create([
            'user_id' => $user->id, 'name' => 'Franco Cerámicas 4', 'document_type' => 'NIT',
            'document_number' => '900222004', 'status' => 'active',
        ]);

        $esf = $this->createStatement($user, $client, 'esf', $this->esfSections());

        $printEsf = $this->actingAs($user)->get(route('financial.print', $esf));
        $printEsf->assertOk();
        $printEsf->assertSee('<div class="signature-box">', false);
        $printEsf->assertSee('TP-12345');

        $pdfEsf = $this->actingAs($user)->get(route('financial.pdf', $esf));
        $pdfEsf->assertOk();
        $pdfEsf->assertHeader('content-type', 'application/pdf');

        $flujoCaja = $this->createBasicFlujoCajaBudget($user, $client);
        $printFlujoCaja = $this->actingAs($user)->get(route('financial.print', $flujoCaja));
        $printFlujoCaja->assertOk();
        $printFlujoCaja->assertDontSee('<div class="signature-box">', false);
    }

    public function test_print_pdf_esf_uses_corporate_header_and_millions(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id, 'name' => 'Franco Cerámicas Corporativo', 'document_type' => 'NIT',
            'person_type' => 'juridica', 'document_number' => '900222008', 'status' => 'active',
        ]);

        $esf = $this->createStatement($user, $client, 'esf', $this->esfSections());

        $print = $this->actingAs($user)->get(route('financial.print', $esf));
        $print->assertOk();
        $print->assertSee('ESTADO DE SITUACIÓN FINANCIERA');
        $print->assertSee('A corte');
        $print->assertSee('Cifras expresadas en millones de pesos colombianos (COP)');
        $print->assertSee('Representante Legal');
        $print->assertDontSee('Presupuesto para');
        $print->assertDontSee('Detalle por período');
        $print->assertDontSee('Períodos proyectados');

        $pdf = $this->actingAs($user)->get(route('financial.pdf', $esf));
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');

        $flujoCaja = $this->createBasicFlujoCajaBudget($user, $client);
        $printFlujoCaja = $this->actingAs($user)->get(route('financial.print', $flujoCaja));
        $printFlujoCaja->assertSee('Presupuesto para');
        $printFlujoCaja->assertDontSee('ESTADO DE SITUACIÓN FINANCIERA');
    }

    public function test_eri_shows_profit_cascade_including_ebitda_everywhere(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 8', 'document_number' => '900222009', 'status' => 'active']);

        $eri = $this->createStatement($user, $client, 'eri', $this->eriSections());

        $eri->load('sections.lines.values');
        $report = $eri->buildEriReport();
        $this->assertEqualsWithDelta(85.0, $report['ebit'][0], 0.01);
        $this->assertEqualsWithDelta(100.0, $report['ebitda'][0], 0.01);

        $show = $this->actingAs($user)->get(route('financial.show', $eri));
        $show->assertOk();
        $show->assertSee('UTILIDAD OPERACIONAL (EBIT)');
        $show->assertSee('EBITDA');
        $show->assertSee('UTILIDAD BRUTA');
        $show->assertSee('UTILIDAD ANTES DE IMPUESTOS (UAI)');
        $show->assertSee('UTILIDAD NETA DEL PERÍODO');

        $print = $this->actingAs($user)->get(route('financial.print', $eri));
        $print->assertOk();
        $print->assertSee('ESTADO DE RESULTADOS');
        $print->assertSee('EBITDA');

        $edit = $this->actingAs($user)->get(route('financial.edit', $eri));
        $edit->assertOk();
        $edit->assertSee('ebitdaForPeriod(p)', false);
    }

    public function test_presupuestos_create_page_bakes_group_awareness(): void
    {
        $user = User::factory()->create();

        // `isStatementType` debe salir del grupo del servidor ($group), no de
        // `selectedType` (vacío hasta que el usuario elige Tipo) — de lo
        // contrario los campos "Nombre del presupuesto"/"Supuestos y notas"
        // se verían un instante antes de tiempo. Estados Financieros ya no
        // comparte esta pantalla/mecanismo — tiene su propia vista combinada
        // ESF+ERI sin selector de tipo (ver StatementPairFlowTest).
        $budgetsCreate = $this->actingAs($user)->get(route('financial.create'));
        $budgetsCreate->assertOk();
        $budgetsCreate->assertSee('isStatementType: false,', false);
    }

    public function test_auto_computed_lines_hidden_input_respects_disabled_state(): void
    {
        // El input oculto que realmente se envía en el POST debe llevar el
        // mismo :disabled que el input visible — si no, cualquier guardado
        // del formulario vuelve a enviar (y congela como manual) el valor
        // actual de "Utilidad del período"/"Depreciaciones y amortizaciones"/
        // "Resultados acumulados", bloqueando "Actualizar vínculos" para siempre.
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 10', 'document_number' => '900222011', 'status' => 'active']);
        $esf = $this->createStatement($user, $client, 'esf', $this->esfSections());

        $create = $this->actingAs($user)->get(route('financial.statements.create', ['client_id' => $client->id]));
        $create->assertSee(':value="line.values[p]" :disabled="isAutoCell(line.name, p)"', false);

        $edit = $this->actingAs($user)->get(route('financial.edit', $esf));
        $edit->assertSee(':value="line.values[p]" :disabled="isAutoCell(line.name, p)"', false);
    }

    public function test_esf_eri_link_does_not_mix_years_when_periods_dont_match(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 9', 'document_number' => '900222010', 'status' => 'active']);

        // ESF con 2 períodos (2026, 2027) vinculado a un ERI de un solo
        // período (2026) — el 2027 del ESF no debe heredar la utilidad 2026.
        // La línea "Utilidad (pérdida) del período" se deja SIN valor (como
        // la deja el formulario real: es una celda deshabilitada/"Auto") para
        // que quede elegible al auto-cálculo de "Actualizar vínculos".
        $esfSectionsNoManualUtilidad = collect($this->esfSections())->map(fn ($s) => [
            ...$s,
            'lines' => collect($s['lines'])->map(fn ($l) => $l['name'] === Budget::ESF_UTILIDAD_LINE
                ? [...$l, 'values' => ['0' => '', '1' => '']]
                : $l)->all(),
        ])->all();
        $esf = $this->createStatement($user, $client, 'esf', $esfSectionsNoManualUtilidad);
        $this->actingAs($user)->put(route('financial.update', $esf), array_merge(
            $this->updatePayload($esf),
            ['periods_count' => 1, 'period_years' => ['0' => 2026, '1' => 2027]]
        ))->assertRedirect();

        $singlePeriodEri = collect($this->eriSections())->map(fn ($s) => [
            ...$s,
            'lines' => collect($s['lines'])->map(fn ($l) => [...$l, 'values' => ['0' => $l['values']['0']]])->all(),
        ])->all();
        $this->actingAs($user)->post(route('financial.store'), [
            'client_id'     => $client->id,
            'name'          => 'ERI de un solo período',
            'type'          => 'eri',
            'base_year'     => 2026,
            'period_type'   => 'annual',
            'periods_count' => 0,
            'period_years'  => ['0' => 2026],
            'sections'      => $singlePeriodEri,
        ])->assertRedirect();
        $eri = Budget::where('user_id', $user->id)->where('type', 'eri')->latest('id')->firstOrFail();

        $esf = $esf->fresh();
        $payload = $this->updatePayload($esf);
        foreach ($payload['sections'] as &$s) {
            foreach ($s['lines'] as &$l) {
                if ($l['name'] === Budget::ESF_UTILIDAD_LINE) {
                    $l['values'] = ['0' => '', '1' => ''];
                }
            }
        }
        unset($s, $l);
        $this->actingAs($user)->put(route('financial.update', $esf), array_merge(
            $payload,
            ['periods_count' => 1, 'period_years' => ['0' => 2026, '1' => 2027], 'linked_counterpart_budget_id' => $eri->id]
        ))->assertRedirect();

        $this->actingAs($user)->post(route('financial.project', $esf->fresh()))->assertRedirect();

        $esf = $esf->fresh();
        $esf->load('sections.lines.values');
        $utilidadLine = $esf->sections->flatMap->lines->firstWhere('name', Budget::ESF_UTILIDAD_LINE);
        $eri->load('sections.lines.values');
        $eriUtilidadNeta2026 = $eri->buildEriReport()['utilidadNeta'][0];

        $this->assertEqualsWithDelta($eriUtilidadNeta2026, $utilidadLine->getValueForPeriod(0), 0.01, 'El período 2026 del ESF sí debe tomar la utilidad 2026 del ERI');
        $this->assertNotEqualsWithDelta($eriUtilidadNeta2026, $utilidadLine->getValueForPeriod(1), 0.01, 'El período 2027 del ESF NO debe heredar la utilidad 2026 del ERI (sin período 2027 en el ERI)');
    }

    private function createBasicFlujoCajaBudget(User $user, Client $client): Budget
    {
        $this->actingAs($user)->post(route('financial.store'), [
            'client_id'     => $client->id,
            'name'          => 'Flujo de caja de prueba',
            'type'          => 'flujo_caja',
            'base_year'     => 2026,
            'period_type'   => 'annual',
            'periods_count' => 1,
            'sections'      => [
                ['name' => 'Entradas', 'lines' => [
                    ['name' => 'Ventas', 'projection_driver' => 'fixed', 'base_value' => 1000],
                ]],
            ],
        ])->assertRedirect();

        return Budget::where('user_id', $user->id)->where('type', 'flujo_caja')->latest('id')->firstOrFail();
    }

    private function updatePayload(Budget $budget, array $overrides = []): array
    {
        $sections = $budget->sections->map(fn ($s) => [
            'name'           => $s->name,
            'is_outflow'     => $s->is_outflow,
            'statement_role' => $s->statement_role,
            'lines' => $s->lines->map(fn ($l) => [
                'name'               => $l->name,
                'projection_driver'  => 'manual',
                'sign_negative'      => $l->sign_negative,
                'values' => collect(range(0, $budget->periods_count))
                    ->mapWithKeys(fn ($i) => [(string) $i => $l->getValueForPeriod($i)])->all(),
            ])->values()->all(),
        ])->values()->all();

        return array_merge([
            'name'          => $budget->name,
            'base_year'     => $budget->base_year,
            'period_type'   => $budget->period_type,
            'periods_count' => $budget->periods_count,
            'period_years'  => collect(range(0, $budget->periods_count))
                ->map(fn ($i) => $budget->calendarYearForPeriod($i))->all(),
            'sections'      => $sections,
        ], $overrides);
    }

    public function test_esf_supports_non_consecutive_custom_period_years(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 5', 'document_number' => '900222005', 'status' => 'active']);

        $this->actingAs($user)->post(route('financial.store'), [
            'client_id'     => $client->id,
            'name'          => 'ESF comparativo',
            'type'          => 'esf',
            'base_year'     => 2024,
            'period_type'   => 'annual',
            'periods_count' => 1,
            'period_years'  => ['0' => 2024, '1' => 2026],
            'sections'      => $this->esfSections(),
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();

        $this->assertSame(2024, $esf->calendarYearForPeriod(0));
        $this->assertSame(2026, $esf->calendarYearForPeriod(1));
        $this->assertSame('2024', $esf->buildPeriodLabel(0));
        $this->assertSame('2026', $esf->buildPeriodLabel(1));

        $show = $this->actingAs($user)->get(route('financial.show', $esf));
        $show->assertOk();
        $show->assertSee('2024');
        $show->assertSee('2026');
        $show->assertDontSee('2025');

        $edit = $this->actingAs($user)->get(route('financial.edit', $esf));
        $edit->assertOk();
        $edit->assertSee('periodYears: [2024,2026]', false);
    }

    public function test_esf_supports_a_single_period(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 6', 'document_number' => '900222006', 'status' => 'active']);

        $singlePeriodSections = collect($this->esfSections())->map(fn ($s) => [
            ...$s,
            'lines' => collect($s['lines'])->map(fn ($l) => [
                ...$l,
                'values' => ['0' => $l['values']['0']],
            ])->all(),
        ])->all();

        $this->actingAs($user)->post(route('financial.store'), [
            'client_id'     => $client->id,
            'name'          => 'ESF de un solo período',
            'type'          => 'esf',
            'base_year'     => 2026,
            'period_type'   => 'annual',
            'periods_count' => 0,
            'period_years'  => ['0' => 2026],
            'sections'      => $singlePeriodSections,
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $this->assertSame(0, $esf->periods_count);

        $esf->load('sections.lines.values');
        $report = $esf->buildEsfReport();
        $this->assertNotNull($report);
        $this->assertCount(1, $report['diferencia']);
        $this->assertLessThan(1, abs($report['diferencia'][0]));
        $this->assertEqualsWithDelta(300.0, $report['totalActivo'][0], 0.01);

        $show = $this->actingAs($user)->get(route('financial.show', $esf));
        $show->assertOk();
        $show->assertSee('cuadra en todos los períodos');

        $edit = $this->actingAs($user)->get(route('financial.edit', $esf));
        $edit->assertOk();
    }

    public function test_flash_messages_and_redirects_are_group_aware(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Franco Cerámicas 7', 'document_number' => '900222007', 'status' => 'active']);

        $esf = $this->createStatement($user, $client, 'esf', $this->esfSections());

        $updateResponse = $this->actingAs($user)->put(route('financial.update', $esf), $this->updatePayload($esf));
        $updateResponse->assertSessionHas('success', 'Estado financiero actualizado.');

        $projectResponse = $this->actingAs($user)->post(route('financial.project', $esf));
        $projectResponse->assertSessionHas('success', 'Vínculos actualizados correctamente.');

        $destroyResponse = $this->actingAs($user)->delete(route('financial.destroy', $esf));
        $destroyResponse->assertRedirect(route('financial.statements.client', $client));
        $destroyResponse->assertSessionHas('success', 'Estado financiero eliminado.');

        $flujoCaja = $this->createBasicFlujoCajaBudget($user, $client);
        $flujoCajaUpdate = $this->actingAs($user)->put(route('financial.update', $flujoCaja), [
            'name' => $flujoCaja->name, 'base_year' => $flujoCaja->base_year, 'period_type' => $flujoCaja->period_type,
            'periods_count' => $flujoCaja->periods_count,
            'sections' => $flujoCaja->sections->map(fn ($s) => [
                'name' => $s->name, 'lines' => $s->lines->map(fn ($l) => [
                    'name' => $l->name, 'projection_driver' => $l->projection_driver, 'base_value' => $l->getValueForPeriod(0),
                ])->all(),
            ])->all(),
        ]);
        $flujoCajaUpdate->assertSessionHas('success', 'Presupuesto actualizado.');

        $flujoCajaDestroy = $this->actingAs($user)->delete(route('financial.destroy', $flujoCaja));
        $flujoCajaDestroy->assertRedirect(route('financial.client', $client));
        $flujoCajaDestroy->assertSessionHas('success', 'Presupuesto eliminado.');
    }
}
