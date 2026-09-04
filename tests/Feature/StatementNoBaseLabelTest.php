<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatementNoBaseLabelTest extends TestCase
{
    use RefreshDatabase;

    private function minimalEsf(): array
    {
        return [
            ['name' => 'Activo Corriente', 'statement_role' => 'activo_corriente', 'lines' => [
                ['name' => 'Efectivo', 'sign_negative' => false, 'values' => ['0' => 10, '1' => 20, '2' => 30, '3' => 40]],
            ]],
            ['name' => 'Patrimonio', 'statement_role' => 'patrimonio', 'lines' => [
                ['name' => 'Capital social', 'sign_negative' => false, 'values' => ['0' => 10, '1' => 20, '2' => 30, '3' => 40]],
                ['name' => \App\Models\Budget::ESF_UTILIDAD_LINE, 'sign_negative' => false, 'values' => ['0' => '', '1' => '', '2' => '', '3' => '']],
            ]],
        ];
    }

    private function minimalEri(): array
    {
        return [
            ['name' => 'Ingresos Operacionales', 'statement_role' => 'ingresos_operacionales', 'lines' => [
                ['name' => 'Ventas', 'sign_negative' => false, 'values' => ['0' => 5, '1' => 10, '2' => 15, '3' => 20]],
            ]],
        ];
    }

    public function test_create_edit_and_show_never_mention_base_period(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Sin Base', 'document_number' => '900666001', 'status' => 'active']);

        $create = $this->actingAs($user)->get(route('financial.statements.create'));
        $create->assertOk();
        $create->assertDontSee('Período base', false);
        $create->assertDontSee("'Base'", false);
        $create->assertDontSee('año base', false);

        $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'four_monthly',
            'periods_count' => 3,
            'period_labels' => ['0' => 'Cuatrimestre 1', '1' => 'Cuatrimestre 2', '2' => 'Cuatrimestre 3', '3' => 'Cuatrimestre 4'],
            'esf_sections'  => $this->minimalEsf(),
            'eri_sections'  => $this->minimalEri(),
        ])->assertRedirect();

        $esf = Budget::where('user_id', $user->id)->where('type', 'esf')->latest('id')->firstOrFail();
        $this->assertSame('Cuatrimestre 1', $esf->buildPeriodLabel(0));

        $edit = $this->actingAs($user)->get(route('financial.statements.edit', $esf));
        $edit->assertOk();
        $edit->assertDontSee('Período base', false);
        $edit->assertDontSee("'Base'", false);
        $edit->assertDontSee('año base', false);
        $edit->assertSee('Cuatrimestre 1', false);

        $show = $this->actingAs($user)->get(route('financial.statements.show', $esf));
        $show->assertOk();
        $show->assertDontSee('Período base', false);
        $show->assertSee('Cuatrimestre 1', false);
    }
}
