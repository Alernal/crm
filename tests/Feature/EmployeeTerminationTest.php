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

class EmployeeTerminationTest extends TestCase
{
    use RefreshDatabase;

    private function baseEmployeeData(Client $client, Employee $employee): array
    {
        return [
            'client_id' => $client->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'document_type' => $employee->document_type,
            'document_number' => $employee->document_number,
            'contract_type' => $employee->contract_type,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'base_salary' => $employee->base_salary,
            'salary_type' => $employee->salary_type,
            'transport_allowance_mode' => $employee->transport_allowance_mode,
            'arl_risk_level' => $employee->arl_risk_level,
            'status' => 'active',
        ];
    }

    public function test_termination_mid_period_prorates_worked_days(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);
        $user = User::factory()->create();

        $client = Client::create([
            'user_id' => $user->id, 'name' => 'Retiro S.A.S.', 'document_number' => '900999888',
            'payroll_periodicity' => 'mensual', 'payroll_prefix' => 'NOM',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id, 'client_id' => $client->id,
            'first_name' => 'Antonio', 'last_name' => 'Gonzalez', 'document_number' => '111222333',
            'hire_date' => '2020-01-01', 'base_salary' => 2000000, 'salary_type' => 'fijo',
        ])->refresh();

        // Registra la novedad: renuncia efectiva el 14 de julio de 2026.
        $updateResponse = $this->actingAs($user)->patch(route('employees.update', $employee), array_merge(
            $this->baseEmployeeData($client, $employee),
            ['termination_reason' => 'renuncia', 'termination_date' => '2026-07-14']
        ));
        $updateResponse->assertRedirect();
        $employee->refresh();
        $this->assertEquals('2026-07-14', $employee->termination_date->format('Y-m-d'));

        // Genera la nómina de julio completo (1-31): debe prorratear a 14 días.
        $this->actingAs($user)->post(route('payroll-periods.store'), [
            'client_id' => $client->id,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'payment_date' => '2026-07-31',
        ])->assertRedirect();

        $julyPeriod = PayrollPeriod::where('client_id', $client->id)->whereDate('start_date', '2026-07-01')->firstOrFail();
        $payroll = Payroll::where('payroll_period_id', $julyPeriod->id)->where('employee_id', $employee->id)->firstOrFail();
        $this->assertEqualsWithDelta(14.0, (float) $payroll->worked_days, 0.01);

        // Genera la nómina de agosto: el trabajador ya no debe aparecer.
        $this->actingAs($user)->post(route('payroll-periods.store'), [
            'client_id' => $client->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'payment_date' => '2026-08-31',
        ])->assertRedirect();

        $augustPeriod = PayrollPeriod::where('client_id', $client->id)->whereDate('start_date', '2026-08-01')->firstOrFail();
        $this->assertEquals(0, Payroll::where('payroll_period_id', $augustPeriod->id)->where('employee_id', $employee->id)->count());
    }
}
