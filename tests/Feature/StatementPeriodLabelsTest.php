<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatementPeriodLabelsTest extends TestCase
{
    use RefreshDatabase;

    private function minimalEsf(): array
    {
        return [
            ['name' => 'Activo Corriente', 'statement_role' => 'activo_corriente', 'lines' => [
                ['name' => 'Efectivo', 'sign_negative' => false, 'values' => ['0' => 100, '1' => 120, '2' => 130]],
            ]],
            ['name' => 'Patrimonio', 'statement_role' => 'patrimonio', 'lines' => [
                ['name' => 'Capital social', 'sign_negative' => false, 'values' => ['0' => 100, '1' => 120, '2' => 130]],
                ['name' => \App\Models\Budget::ESF_UTILIDAD_LINE, 'sign_negative' => false, 'values' => ['0' => '', '1' => '', '2' => '']],
            ]],
        ];
    }

    private function minimalEri(): array
    {
        return [
            ['name' => 'Ingresos Operacionales', 'statement_role' => 'ingresos_operacionales', 'lines' => [
                ['name' => 'Ventas', 'sign_negative' => false, 'values' => ['0' => 50, '1' => 60, '2' => 70]],
            ]],
        ];
    }

    public function test_monthly_statement_defaults_to_mes_n_labels_and_is_editable(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Mensual', 'document_number' => '900777001', 'status' => 'active']);

        $store = $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'monthly',
            'periods_count' => 2,
            'cutoff_date'   => '2026-03-31',
            'period_labels' => ['0' => 'Mes 1', '1' => 'Mes 2', '2' => 'Marzo (cierre)'],
            'esf_sections'  => $this->minimalEsf(),
            'eri_sections'  => $this->minimalEri(),
        ]);
        $store->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $this->assertSame(2026, $esf->base_year);
        $this->assertNull($esf->period_years);
        $this->assertSame(['Mes 1', 'Mes 2', 'Marzo (cierre)'], $esf->period_labels);
        $this->assertSame('2026-03-31', $esf->cutoff_date->format('Y-m-d'));

        $this->assertSame(
            ['Mes 1', 'Mes 2', 'Marzo (cierre)'],
            array_values($esf->getPeriodLabels())
        );
    }

    public function test_four_monthly_statement_uses_cuatrimestre_default_label(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Cuatri', 'document_number' => '900777002', 'status' => 'active']);

        $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'four_monthly',
            'periods_count' => 1,
            'period_labels' => ['0' => 'Cuatrimestre 1', '1' => 'Cuatrimestre 2'],
            'esf_sections'  => [
                ['name' => 'Activo Corriente', 'statement_role' => 'activo_corriente', 'lines' => [
                    ['name' => 'Efectivo', 'sign_negative' => false, 'values' => ['0' => 10, '1' => 20]],
                ]],
                ['name' => 'Patrimonio', 'statement_role' => 'patrimonio', 'lines' => [
                    ['name' => 'Capital social', 'sign_negative' => false, 'values' => ['0' => 10, '1' => 20]],
                    ['name' => \App\Models\Budget::ESF_UTILIDAD_LINE, 'sign_negative' => false, 'values' => ['0' => '', '1' => '']],
                ]],
            ],
            'eri_sections'  => [
                ['name' => 'Ingresos Operacionales', 'statement_role' => 'ingresos_operacionales', 'lines' => [
                    ['name' => 'Ventas', 'sign_negative' => false, 'values' => ['0' => 5, '1' => 10]],
                ]],
            ],
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $this->assertSame(['Cuatrimestre 1', 'Cuatrimestre 2'], $esf->period_labels);
        $this->assertSame('Cuatrimestre 1', $esf->buildPeriodLabel(0));
        $this->assertSame('Cuatrimestre 2', $esf->buildPeriodLabel(1));
    }

    public function test_annual_statement_still_uses_period_years_unaffected(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Anual', 'document_number' => '900777003', 'status' => 'active']);

        $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'annual',
            'periods_count' => 1,
            'period_years'  => ['0' => 2024, '1' => 2026],
            'esf_sections'  => $this->minimalEsf(),
            'eri_sections'  => $this->minimalEri(),
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $this->assertSame([2024, 2026], $esf->period_years);
        $this->assertNull($esf->period_labels);
        $this->assertSame('2024', $esf->buildPeriodLabel(0));
        $this->assertSame('2026', $esf->buildPeriodLabel(1));
    }

    public function test_cutoff_date_shows_on_pdf_a_corte_line(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente PDF', 'document_number' => '900777004', 'status' => 'active']);

        $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'quarterly',
            'periods_count' => 0,
            'cutoff_date'   => '2026-09-15',
            'period_labels' => ['0' => 'Trimestre 1'],
            'esf_sections'  => $this->minimalEsf(),
            'eri_sections'  => $this->minimalEri(),
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();

        $pdf = $this->actingAs($user)->get(route('financial.pdf', $esf));
        $pdf->assertOk();

        $print = $this->actingAs($user)->get(route('financial.print', $esf));
        $print->assertOk();
        $print->assertSee('15 de septiembre de 2026', false);
    }

    public function test_edit_page_prefills_period_labels_and_cutoff_date(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Editar Etiquetas', 'document_number' => '900777005', 'status' => 'active']);

        $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'semiannual',
            'periods_count' => 1,
            'cutoff_date'   => '2026-06-30',
            'period_labels' => ['0' => 'Semestre 1', '1' => 'Semestre 2'],
            'esf_sections'  => $this->minimalEsf(),
            'eri_sections'  => $this->minimalEri(),
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();

        $edit = $this->actingAs($user)->get(route('financial.statements.edit', $esf));
        $edit->assertOk();
        $edit->assertSee('Semestre 1', false);
        $edit->assertSee('Semestre 2', false);
        $edit->assertSee("cutoffDate: '2026-06-30'", false);
    }
}
