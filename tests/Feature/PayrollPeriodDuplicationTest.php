<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\PayrollLegalSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollPeriodDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicating_a_period_copies_manual_concepts_and_overtime(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);
        $user = User::factory()->create();

        $client = Client::create([
            'user_id' => $user->id, 'name' => 'Duplicado S.A.S.', 'document_number' => '900555666',
            'payroll_periodicity' => 'mensual', 'payroll_prefix' => 'NOM',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id, 'client_id' => $client->id,
            'first_name' => 'Sofia', 'last_name' => 'Rojas', 'document_number' => '999888777',
            'hire_date' => '2020-01-01', 'base_salary' => 2000000, 'salary_type' => 'fijo',
        ]);

        // Período de junio con comisiones, un descuento y horas extra.
        $this->actingAs($user)->post(route('payroll-periods.store'), [
            'client_id' => $client->id,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'payment_date' => '2026-06-30',
        ])->assertRedirect();

        $junePeriod = PayrollPeriod::where('client_id', $client->id)->whereDate('start_date', '2026-06-01')->firstOrFail();
        $junePayroll = Payroll::where('payroll_period_id', $junePeriod->id)->where('employee_id', $employee->id)->firstOrFail();

        $this->actingAs($user)->patch(route('payrolls.update', $junePayroll), [
            'worked_days' => 30,
            'commissions' => 80000,
            'loans_deduction' => 40000,
        ])->assertRedirect();

        $this->actingAs($user)->put(route('payrolls.overtime.update', $junePayroll), [
            'items' => [
                ['type' => 'extra_diurna', 'hours' => 3],
            ],
        ])->assertRedirect();

        $junePayroll->refresh();
        $this->assertEquals(80000.0, (float) $junePayroll->commissions);
        $this->assertEquals(40000.0, (float) $junePayroll->loans_deduction);
        $this->assertGreaterThan(0, (float) $junePayroll->overtime_pay);

        // Genera julio copiando desde junio.
        $this->actingAs($user)->post(route('payroll-periods.store'), [
            'client_id' => $client->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'payment_date' => '2026-07-31',
            'copy_from_period_id' => $junePeriod->id,
        ])->assertRedirect();

        $julyPeriod = PayrollPeriod::where('client_id', $client->id)->whereDate('start_date', '2026-07-01')->firstOrFail();
        $julyPayroll = Payroll::where('payroll_period_id', $julyPeriod->id)->where('employee_id', $employee->id)->firstOrFail();

        $this->assertEquals(80000.0, (float) $julyPayroll->commissions);
        $this->assertEquals(40000.0, (float) $julyPayroll->loans_deduction);
        $this->assertEquals(1, $julyPayroll->overtimeItems()->count());
        $this->assertEquals((float) $junePayroll->overtime_pay, (float) $julyPayroll->overtime_pay);

        // Los días trabajados siguen calculándose de cero para el período nuevo (30 días completos), no copiados literalmente.
        $this->assertEqualsWithDelta(30.0, (float) $julyPayroll->worked_days, 0.01);

        // El período nuevo sigue siendo editable de forma independiente (no afecta al de origen).
        $this->actingAs($user)->patch(route('payrolls.update', $julyPayroll), [
            'worked_days' => 30,
            'commissions' => 999,
        ])->assertRedirect();
        $julyPayroll->refresh();
        $junePayroll->refresh();
        $this->assertEquals(999.0, (float) $julyPayroll->commissions);
        $this->assertEquals(80000.0, (float) $junePayroll->commissions);
    }

    public function test_copy_from_period_id_must_belong_to_same_client(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);
        $user = User::factory()->create();

        $clientA = Client::create(['user_id' => $user->id, 'name' => 'A', 'document_number' => '1', 'payroll_periodicity' => 'mensual']);
        $clientB = Client::create(['user_id' => $user->id, 'name' => 'B', 'document_number' => '2', 'payroll_periodicity' => 'mensual']);

        $periodA = PayrollPeriod::create([
            'user_id' => $user->id, 'client_id' => $clientA->id, 'number' => 'NOM-0001',
            'period_type' => 'mensual', 'start_date' => '2026-06-01', 'end_date' => '2026-06-30', 'payment_date' => '2026-06-30',
        ]);

        $this->actingAs($user)->post(route('payroll-periods.store'), [
            'client_id' => $clientB->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'payment_date' => '2026-07-31',
            'copy_from_period_id' => $periodA->id,
        ])->assertStatus(422);
    }
}
