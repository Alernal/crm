<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatementPairFlowTest extends TestCase
{
    use RefreshDatabase;

    private function esfSections(): array
    {
        return [
            ['name' => 'Activo Corriente', 'statement_role' => 'activo_corriente', 'lines' => [
                ['name' => 'Efectivo y equivalentes', 'sign_negative' => false, 'values' => ['0' => 100, '1' => 120]],
            ]],
            ['name' => 'Activo No Corriente', 'statement_role' => 'activo_no_corriente', 'lines' => [
                ['name' => 'Propiedades, planta y equipo — bruto', 'sign_negative' => false, 'values' => ['0' => 200, '1' => 200]],
            ]],
            ['name' => 'Pasivo Corriente', 'statement_role' => 'pasivo_corriente', 'lines' => [
                ['name' => 'Cuentas por pagar proveedores', 'sign_negative' => false, 'values' => ['0' => 80, '1' => 90]],
            ]],
            ['name' => 'Patrimonio', 'statement_role' => 'patrimonio', 'lines' => [
                ['name' => 'Capital social', 'sign_negative' => false, 'values' => ['0' => 220, '1' => 220]],
                ['name' => \App\Models\Budget::ESF_UTILIDAD_LINE, 'sign_negative' => false, 'values' => ['0' => '', '1' => '']],
            ]],
        ];
    }

    private function eriSections(): array
    {
        return [
            ['name' => 'Ingresos Operacionales', 'statement_role' => 'ingresos_operacionales', 'lines' => [
                ['name' => 'Ventas', 'sign_negative' => false, 'values' => ['0' => 500, '1' => 600]],
            ]],
            ['name' => 'Costo de Ventas', 'statement_role' => 'costo_ventas', 'lines' => [
                ['name' => 'Costo de ventas', 'sign_negative' => true, 'values' => ['0' => 300, '1' => 350]],
            ]],
            ['name' => 'Impuestos', 'statement_role' => 'impuestos', 'lines' => [
                ['name' => 'Impuesto de renta', 'sign_negative' => true, 'values' => ['0' => 40, '1' => 50]],
            ]],
        ];
    }

    private function storePayload(int $clientId): array
    {
        return [
            'client_id'     => $clientId,
            'period_type'   => 'annual',
            'periods_count' => 1,
            'period_years'  => ['0' => 2026, '1' => 2027],
            'esf_sections'  => $this->esfSections(),
            'eri_sections'  => $this->eriSections(),
        ];
    }

    public function test_create_page_has_no_type_selector_and_shows_both_tabs(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('financial.statements.create'));
        $response->assertOk();
        $response->assertSee('Situación Financiera', false);
        $response->assertSee('Resultados', false);
        $response->assertSee('esfSections', false);
        $response->assertSee('eriSections', false);
        $response->assertDontSee('name="type"', false);
        $response->assertDontSee('Vincular con Estado', false);
    }

    public function test_store_creates_both_statements_linked_with_shared_period(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Par', 'document_number' => '900111111', 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $eri = Budget::where('user_id', $user->id)->where('type', 'eri')->latest('id')->firstOrFail();

        $response->assertRedirect(route('financial.statements.show', $esf));

        $this->assertSame($eri->id, $esf->linked_counterpart_budget_id);
        $this->assertSame($esf->id, $eri->linked_counterpart_budget_id);
        $this->assertSame($esf->base_year, $eri->base_year);
        $this->assertSame($esf->period_type, $eri->period_type);
        $this->assertSame($esf->periods_count, $eri->periods_count);
        $this->assertSame('Estado de Situación Financiera 2026-2027', $esf->name);
        $this->assertSame('Estado de Resultados 2026-2027', $eri->name);
    }

    public function test_show_links_eri_net_income_into_esf_patrimonio_automatically(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Vinculo', 'document_number' => '900111119', 'status' => 'active']);
        $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));
        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $eri = Budget::where('user_id', $user->id)->where('type', 'eri')->latest('id')->firstOrFail();

        $this->actingAs($user)->get(route('financial.statements.show', $esf))->assertOk();

        $esf->load('sections.lines.values');
        $eri->load('sections.lines.values');
        $eriReport = $eri->buildEriReport();
        $utilidadLine = $esf->sections->flatMap->lines->firstWhere('name', \App\Models\Budget::ESF_UTILIDAD_LINE);

        $this->assertEqualsWithDelta($eriReport['utilidadNeta'][0], $utilidadLine->getValueForPeriod(0), 0.01);
    }

    public function test_show_page_renders_both_statements_with_tab_switcher(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Ver', 'document_number' => '900111112', 'status' => 'active']);
        $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));
        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('financial.statements.show', $esf));
        $response->assertOk();
        $response->assertSee('Situación Financiera', false);
        $response->assertSee('Resultados', false);
        $response->assertSee('Efectivo y equivalentes', false);
        $response->assertSee('Ventas', false);
    }

    public function test_edit_and_update_saves_both_statements_together(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Editar', 'document_number' => '900111113', 'status' => 'active']);
        $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));
        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $eri = Budget::where('user_id', $user->id)->where('type', 'eri')->latest('id')->firstOrFail();

        $edit = $this->actingAs($user)->get(route('financial.statements.edit', $esf));
        $edit->assertOk();
        $edit->assertSee('Efectivo y equivalentes', false);
        $edit->assertSee('Ventas', false);

        $esfSections = $this->esfSections();
        $esfSections[0]['lines'][0]['values'] = ['0' => 999, '1' => 999];
        $eriSections = $this->eriSections();
        $eriSections[0]['lines'][0]['values'] = ['0' => 777, '1' => 777];

        $update = $this->actingAs($user)->put(route('financial.statements.update', $esf), [
            'period_type'   => 'annual',
            'periods_count' => 1,
            'period_years'  => ['0' => 2026, '1' => 2027],
            'status'        => 'final',
            'esf_sections'  => $esfSections,
            'eri_sections'  => $eriSections,
        ]);
        $update->assertRedirect(route('financial.statements.show', $esf));

        $esf->refresh(); $eri->refresh();
        $this->assertSame('final', $esf->status);
        $this->assertSame('final', $eri->status);
        $esf->load('sections.lines.values');
        $eri->load('sections.lines.values');
        $efectivoLine = $esf->sections->flatMap->lines->firstWhere('name', 'Efectivo y equivalentes');
        $ventasLine   = $eri->sections->flatMap->lines->firstWhere('name', 'Ventas');
        $this->assertEquals(999.0, $efectivoLine->getValueForPeriod(0));
        $this->assertEquals(777.0, $ventasLine->getValueForPeriod(0));
    }

    public function test_editing_eri_cell_returns_esf_counterpart_html_with_updated_utilidad(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Tiempo Real', 'document_number' => '900111120', 'status' => 'active']);
        $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));
        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $eri = Budget::where('user_id', $user->id)->where('type', 'eri')->latest('id')->firstOrFail();

        $this->actingAs($user)->get(route('financial.statements.show', $esf))->assertOk();

        $ventasLine = $eri->sections()->with('lines')->get()->flatMap->lines->firstWhere('name', 'Ventas');

        $response = $this->actingAs($user)->postJson(route('financial.update_value', $eri), [
            'line_id'      => $ventasLine->id,
            'period_index' => 0,
            'value'        => 900,
            'value_type'   => 'budgeted',
        ]);
        $response->assertOk();

        // La respuesta de editar una celda del ERI debe traer también el HTML
        // fresco del ESF vinculado — sin esto, el tab del ESF (ya presente en
        // el DOM de la misma página, oculto vía x-show) se queda con la
        // "Utilidad del período" y el badge Cuadra/Descuadre desactualizados
        // hasta que el usuario recarga la página a mano.
        $response->assertJsonStructure(['ok', 'html', 'counterpart_id', 'counterpart_html']);
        $this->assertSame($esf->id, $response->json('counterpart_id'));

        $esf->load('sections.lines.values');
        $eri->load('sections.lines.values');
        $eriReport = $eri->buildEriReport();
        $utilidadLine = $esf->sections->flatMap->lines->firstWhere('name', \App\Models\Budget::ESF_UTILIDAD_LINE);

        $this->assertEqualsWithDelta($eriReport['utilidadNeta'][0], $utilidadLine->getValueForPeriod(0), 0.01);
        $this->assertStringContainsString('Estado del balance', $response->json('counterpart_html'));
    }

    public function test_utilidad_neta_is_matched_by_period_index_not_calendar_year_for_monthly_statements(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Mensual', 'document_number' => '900111121', 'status' => 'active']);

        // 3 meses del mismo año calendario: emparejar solo por año colapsaría
        // los 3 períodos del ESF contra el primer mes del ERI (el bug real
        // reportado: "Utilidad del período" repetida idéntica en los 3 meses).
        $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'monthly',
            'periods_count' => 2,
            'esf_sections'  => [
                ['name' => 'Activo Corriente', 'statement_role' => 'activo_corriente', 'lines' => [
                    ['name' => 'Efectivo', 'sign_negative' => false, 'values' => ['0' => 100, '1' => 100, '2' => 100]],
                ]],
                ['name' => 'Patrimonio', 'statement_role' => 'patrimonio', 'lines' => [
                    ['name' => 'Capital social', 'sign_negative' => false, 'values' => ['0' => 100, '1' => 100, '2' => 100]],
                    ['name' => \App\Models\Budget::ESF_UTILIDAD_LINE, 'sign_negative' => false, 'values' => ['0' => '', '1' => '', '2' => '']],
                ]],
            ],
            'eri_sections'  => [
                ['name' => 'Ingresos Operacionales', 'statement_role' => 'ingresos_operacionales', 'lines' => [
                    ['name' => 'Ventas', 'sign_negative' => false, 'values' => ['0' => 100, '1' => 200, '2' => 300]],
                ]],
            ],
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $eri = Budget::where('user_id', $user->id)->where('type', 'eri')->latest('id')->firstOrFail();

        $this->actingAs($user)->get(route('financial.statements.show', $esf))->assertOk();

        $esf->load('sections.lines.values');
        $eri->load('sections.lines.values');
        $eriReport = $eri->buildEriReport();
        $utilidadLine = $esf->sections->flatMap->lines->firstWhere('name', \App\Models\Budget::ESF_UTILIDAD_LINE);

        // Cada mes del ESF debe reflejar la utilidad neta de SU PROPIO mes en
        // el ERI (100, 200, 300), no la del mes 0 repetida en los 3.
        $this->assertEqualsWithDelta($eriReport['utilidadNeta'][0], $utilidadLine->getValueForPeriod(0), 0.01);
        $this->assertEqualsWithDelta($eriReport['utilidadNeta'][1], $utilidadLine->getValueForPeriod(1), 0.01);
        $this->assertEqualsWithDelta($eriReport['utilidadNeta'][2], $utilidadLine->getValueForPeriod(2), 0.01);
        $this->assertNotEqualsWithDelta($utilidadLine->getValueForPeriod(0), $utilidadLine->getValueForPeriod(2), 0.01);
    }

    public function test_create_and_edit_pages_preview_auto_cells_live_via_alpine(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Vista Previa', 'document_number' => '900111122', 'status' => 'active']);

        $create = $this->actingAs($user)->get(route('financial.statements.create'));
        $create->assertOk();
        // "Utilidad (pérdida) del período" y "Depreciaciones y amortizaciones"
        // ya no se quedan en blanco hasta guardar: el formulario calcula su
        // vista previa en vivo con Alpine mientras el usuario digita en el
        // otro estado vinculado.
        $create->assertSee(':value="formatGridNumber(autoCellDisplayValue(line.name, p))"', false);
        $create->assertSee('esfUtilidadLineName:', false);
        $create->assertSee('eriDeprecGastoLineName:', false);
        // Los totales en vivo (TOTAL PATRIMONIO, EBIT/EBITDA...) deben sumar
        // el valor calculado de las celdas "Auto", no el `line.values[p]`
        // crudo (que nunca se llena para esas 2 líneas) — de lo contrario
        // la celda de Utilidad del período muestra el valor correcto pero
        // TOTAL PATRIMONIO sigue sin incluirlo (bug real reportado).
        $create->assertSee('sum += line.signNegative ? -this.lineValueForPeriod(line, p) : this.lineValueForPeriod(line, p);', false);

        $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));
        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();

        $edit = $this->actingAs($user)->get(route('financial.statements.edit', $esf));
        $edit->assertOk();
        $edit->assertSee(':value="formatGridNumber(autoCellDisplayValue(line.name, p))"', false);
        $edit->assertSee('sum += line.signNegative ? -this.lineValueForPeriod(line, p) : this.lineValueForPeriod(line, p);', false);
    }

    public function test_destroy_removes_both_statements(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Eliminar', 'document_number' => '900111114', 'status' => 'active']);
        $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));
        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $eri = Budget::where('user_id', $user->id)->where('type', 'eri')->latest('id')->firstOrFail();

        $response = $this->actingAs($user)->delete(route('financial.statements.destroy', $esf));
        $response->assertRedirect(route('financial.statements.client', $client->id));

        $this->assertNull(Budget::find($esf->id));
        $this->assertNull(Budget::find($eri->id));
    }

    public function test_client_list_shows_one_row_per_pair(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Listado', 'document_number' => '900111115', 'status' => 'active']);
        $this->actingAs($user)->post(route('financial.statements.store'), $this->storePayload($client->id));

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();

        $response = $this->actingAs($user)->get(route('financial.statements.client', $client));
        $response->assertOk();
        $response->assertSee(route('financial.statements.show', $esf), false);
        $this->assertSame(1, substr_count($response->getContent(), 'title="Ver estados financieros"'));
    }
}
