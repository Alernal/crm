<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ContractSettlement;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\PayrollLegalSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recorre el flujo real del liquidador de contratos a través de la pila HTTP
 * completa (rutas -> controlador -> Blade -> BD), incluyendo el wizard, el
 * PDF y la impresión, para detectar errores 500 o de sintaxis Blade que el
 * test unitario del motor de cálculo no vería.
 */
class ContractSettlementFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(User $user, Client $client, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'first_name' => 'Laura',
            'last_name' => 'Rodríguez',
            'document_type' => 'CC',
            'document_number' => '1099887766',
            'position' => 'Analista de nómina',
            'contract_type' => 'indefinido',
            'hire_date' => '2024-01-16',
            'base_salary' => 2848000,
            'salary_type' => 'fijo',
            'arl_risk_level' => 'I',
            'status' => 'inactive',
        ], $overrides));
    }

    public function test_full_settlement_flow_from_wizard_to_pdf(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Liquidación S.A.S.',
            'document_number' => '900555666',
            'dv' => '2',
        ]);

        $employee = $this->makeEmployee($user, $client, [
            'termination_date' => '2026-04-30',
            'termination_reason' => 'despido_sin_justa_causa',
        ]);

        // Sin fecha de retiro todavía no debería mostrar el botón de liquidar.
        $this->actingAs($user)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Liquidar contrato');

        $wizard = $this->actingAs($user)->get(route('contract-settlements.create', ['employee_id' => $employee->id]));
        $wizard->assertOk();
        $wizard->assertSee('Liquidar contrato');

        $store = $this->actingAs($user)->post(route('contract-settlements.store'), [
            'employee_id' => $employee->id,
            'contract_type' => 'indefinido',
            'smlv' => 1750905,
            'last_salary' => 2848000,
            'basic_salary' => 2848000,
            'transport_allowance_input' => 249095,
            'worked_days_month' => 30,
            'indemnification_applies' => '1',
            'year_start_date' => '2026-01-01',
            'vacation_period_start' => '2026-01-16',
            'prima_period_start' => '2026-01-01',
            'contract_end_date' => '2026-04-30',
            'contract_reference_date' => '2024-01-16',
            'overtime_value' => 101000,
            'recargos_value' => 52000,
            'commissions' => 150000,
            'bonuses_salarial' => 200000,
            'other_salarial' => 125000,
            'occasional_bonuses' => 100000,
            'extralegal_premiums' => 150000,
            'per_diem_no_salarial' => 2500000,
            'other_no_salarial' => 190000,
            'withholding_tax' => 55000,
            'other_deductions' => 250000,
        ]);

        $settlement = ContractSettlement::firstOrFail();
        $store->assertRedirect(route('contract-settlements.show', $settlement));

        $this->assertEqualsWithDelta(13859588.65, (float) $settlement->net_pay, 0.5);
        $this->assertEqualsWithDelta(5298060.27, (float) $settlement->indemnification_value, 0.5);

        $this->actingAs($user)->get(route('contract-settlements.show', $settlement))
            ->assertOk()
            ->assertSee('Neto a pagar');

        $this->actingAs($user)->get(route('contract-settlements.index'))
            ->assertOk()
            ->assertSee($employee->full_name);

        $this->actingAs($user)->get(route('contract-settlements.print', $settlement))->assertOk();
        $this->actingAs($user)->get(route('contract-settlements.pdf', $settlement))->assertOk();

        $destroy = $this->actingAs($user)->delete(route('contract-settlements.destroy', $settlement));
        $destroy->assertRedirect(route('contract-settlements.index'));
        $this->assertDatabaseMissing('contract_settlements', ['id' => $settlement->id]);
    }

    public function test_cannot_create_settlement_without_termination_date(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Sin Retiro S.A.S.',
            'document_number' => '900555667',
            'dv' => '2',
        ]);

        $employee = $this->makeEmployee($user, $client, ['status' => 'active']);

        $this->actingAs($user)
            ->get(route('contract-settlements.create', ['employee_id' => $employee->id]))
            ->assertStatus(422);
    }

    public function test_wizard_suggests_cesantias_base_from_hire_date_when_hired_mid_year(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Ingreso Reciente S.A.S.',
            'document_number' => '900555670',
            'dv' => '2',
        ]);

        // Ingresó el 14 de julio de 2026 y se retira el mismo año: la base de
        // cesantías debe partir de la fecha de ingreso, no del 1 de enero,
        // o se estarían liquidando días en los que el empleado no trabajó.
        $employee = $this->makeEmployee($user, $client, [
            'hire_date' => '2026-07-14',
            'termination_date' => '2026-10-31',
            'termination_reason' => 'renuncia',
        ]);

        $wizard = $this->actingAs($user)->get(route('contract-settlements.create', ['employee_id' => $employee->id]));

        $wizard->assertOk();
        $wizard->assertSee('name="year_start_date" value="2026-07-14"', false);
        $wizard->assertDontSee('name="year_start_date" value="2026-01-01"', false);
    }

    public function test_cannot_create_settlement_for_apprenticeship_contract(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Aprendiz S.A.S.',
            'document_number' => '900555668',
            'dv' => '2',
        ]);

        $employee = $this->makeEmployee($user, $client, [
            'contract_type' => 'aprendizaje',
            'termination_date' => '2026-04-30',
            'termination_reason' => 'renuncia',
        ]);

        $this->actingAs($user)->get(route('employees.show', $employee))
            ->assertOk()
            ->assertDontSee('Liquidar contrato');

        $this->actingAs($user)
            ->get(route('contract-settlements.create', ['employee_id' => $employee->id]))
            ->assertStatus(422);
    }

    public function test_settlements_are_isolated_between_users(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $client = Client::create([
            'user_id' => $owner->id,
            'name' => 'Cliente Aislado S.A.S.',
            'document_number' => '900555669',
            'dv' => '2',
        ]);

        $employee = $this->makeEmployee($owner, $client, [
            'termination_date' => '2026-04-30',
            'termination_reason' => 'renuncia',
        ]);

        $settlement = ContractSettlement::create([
            'user_id' => $owner->id,
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'smlv' => 1750905, 'last_salary' => 2848000, 'basic_salary' => 2848000,
            'transport_allowance_input' => 249095, 'worked_days_month' => 30,
            'indemnification_applies' => false, 'contract_type' => 'indefinido',
            'year_start_date' => '2026-01-01', 'vacation_period_start' => '2026-01-16',
            'prima_period_start' => '2026-01-01', 'contract_end_date' => '2026-04-30',
            'contract_reference_date' => '2024-01-16',
            'prima_base' => 3097095, 'prima_days' => 120, 'prima_value' => 1032365,
            'cesantias_base' => 3097095, 'cesantias_days' => 120, 'cesantias_value' => 1032365,
            'interest_cesantias_value' => 41294.6,
            'vacation_base' => 2848000, 'vacation_days' => 105, 'vacation_value' => 415333.33,
            'basic_salary_pay' => 2848000, 'ibc_salarial' => 3476000, 'ibc_no_salarial' => 3189095,
            'ibc_excess' => 523057, 'ibc' => 3999057,
            'health_employee' => 159962.28, 'pension_employee' => 159962.28, 'fsp_employee' => 0,
            'indemnification_value' => 0,
            'total_to_pay' => 14484513.21, 'total_deductions' => 624924.56, 'net_pay' => 13859588.65,
        ]);

        $this->actingAs($intruder)->get(route('contract-settlements.show', $settlement))->assertForbidden();
        $this->actingAs($intruder)->get(route('contract-settlements.pdf', $settlement))->assertForbidden();
        $this->actingAs($intruder)->delete(route('contract-settlements.destroy', $settlement))->assertForbidden();
    }
}
