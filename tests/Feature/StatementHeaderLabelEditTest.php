<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatementHeaderLabelEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_label_inputs_moved_into_column_headers_not_parametrizacion(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Encabezados', 'document_number' => '900555001', 'status' => 'active']);

        $create = $this->actingAs($user)->get(route('financial.statements.create'));
        $create->assertOk();
        $create->assertDontSee('Cada período trae un nombre sugerido', false);
        $create->assertSee(':name="`period_labels[${p}]`"', false);
        $create->assertSee('El nombre de cada período se edita directamente en el encabezado de su columna', false);

        $this->actingAs($user)->post(route('financial.statements.store'), [
            'client_id'     => $client->id,
            'period_type'   => 'quarterly',
            'periods_count' => 1,
            'period_labels' => ['0' => 'Trimestre 1', '1' => 'Trimestre 2'],
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
        $this->assertSame(['Trimestre 1', 'Trimestre 2'], $esf->period_labels);

        $edit = $this->actingAs($user)->get(route('financial.statements.edit', $esf));
        $edit->assertOk();
        $edit->assertDontSee('Cada período trae un nombre sugerido', false);
        $edit->assertSee(':name="`period_labels[${p}]`"', false);
    }
}
