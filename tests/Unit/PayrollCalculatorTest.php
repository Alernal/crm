<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Services\Payroll\PayrollCalculator;
use Database\Seeders\PayrollLegalSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valida el motor de cálculo contra los valores exactos que arroja el
 * liquidador de Actualícese (storage/app/Nómina/2. Liquidador y desprendible
 * de nómina básico.xlsm, hoja "Liquidación nómina") para el caso de ejemplo:
 * salario 2.000.000, 30 días laborados, empleador exonerado art. 114-1 ET,
 * riesgo ARL I, sin novedades.
 */
class PayrollCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(array $overrides = []): Employee
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Actualícese Ltda.',
            'document_number' => '900069482',
            'dv' => '1',
            'payroll_pila_exempt' => true,
        ]);

        return Employee::create(array_merge([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'first_name' => 'María',
            'last_name' => 'Sostarte',
            'document_number' => '55555555',
            'position' => 'Asistente de edición',
            'hire_date' => '2020-01-01',
            'base_salary' => 2000000,
            'salary_type' => 'fijo',
            'arl_risk_level' => 'I',
        ], $overrides));
    }

    private function makePeriod(Employee $employee, string $startDate = '2026-01-01'): PayrollPeriod
    {
        return PayrollPeriod::create([
            'user_id' => $employee->user_id,
            'client_id' => $employee->client_id,
            'number' => 'NOM-0001',
            'period_type' => 'mensual',
            'start_date' => $startDate,
            'end_date' => '2026-01-30',
            'payment_date' => '2026-01-30',
        ]);
    }

    public function test_basic_monthly_payroll_matches_excel_reference(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $employee = $this->makeEmployee();
        $period = $this->makePeriod($employee);

        $result = (new PayrollCalculator())->calculate(
            employee: $employee,
            client: $employee->client,
            period: $period,
            workedDays: 30,
            manual: [
                'commissions' => 50000,
                'bonuses_salarial' => 50000,
                'per_diem_salarial' => 50000,
                'other_salarial' => 10000,
                'occasional_bonuses' => 500000,
                'extralegal_premiums' => 500000,
                'per_diem_no_salarial' => 150000,
                'other_no_salarial' => 150000,
                'loans_deduction' => 250000,
                'withholding_tax' => 0,
            ],
            overtimeByType: [
                'dominical_festivo' => 25000,
                'recargo_nocturno' => 15000,
            ],
        );

        $this->assertSame(2000000.0, $result['basic_salary_pay']);
        $this->assertSame(40000.0, $result['overtime_pay']);
        $this->assertSame(2200000.0, $result['subtotal_salarial']);
        $this->assertSame(249095.0, $result['transport_allowance']);
        $this->assertSame(1549095.0, $result['subtotal_no_salarial']);
        $this->assertSame(3749095.0, $result['total_earned']);
        $this->assertSame(2249457.0, $result['ibc']);
        $this->assertSame(89978.28, $result['health_employee']);
        $this->assertSame(89978.28, $result['pension_employee']);
        $this->assertSame(0.0, $result['health_employer']);
        $this->assertSame(269934.84, $result['pension_employer']);
        $this->assertEqualsWithDelta(11742.17, $result['arl_employer'], 0.01);
        $this->assertSame(89978.28, $result['caja_compensacion']);
        $this->assertSame(0.0, $result['icbf']);
        $this->assertSame(0.0, $result['sena']);
        $this->assertSame(0.0, $result['fsp_employee']);
        $this->assertSame(429956.56, $result['total_deductions']);
        $this->assertSame(3319138.44, $result['net_pay']);
        $this->assertEqualsWithDelta(204091.17, $result['prima_provision'], 0.05);
        $this->assertEqualsWithDelta(204091.17, $result['cesantias_provision'], 0.05);
        $this->assertEqualsWithDelta(2040.91, $result['interest_cesantias_provision'], 0.05);
        $this->assertEqualsWithDelta(90624.86, $result['vacation_provision'], 0.05);
    }

    public function test_transport_allowance_is_denied_when_override_is_never(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $employee = $this->makeEmployee([
            'base_salary' => 1800000,
            'transport_allowance_mode' => 'nunca',
            'transport_allowance_note' => 'Vive en las instalaciones de la empresa',
        ]);
        $period = $this->makePeriod($employee);

        $result = (new PayrollCalculator())->calculate($employee, $employee->client, $period, 30);

        $this->assertSame(0.0, $result['transport_allowance']);
    }

    public function test_transport_allowance_is_denied_above_two_smlv(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $employee = $this->makeEmployee(['base_salary' => 4000000]);
        $period = $this->makePeriod($employee);

        $result = (new PayrollCalculator())->calculate($employee, $employee->client, $period, 30);

        $this->assertSame(0.0, $result['transport_allowance']);
    }

    public function test_pension_exempt_employee_has_no_pension_deduction_or_contribution(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $employee = $this->makeEmployee(['pension_exempt' => true]);
        $period = $this->makePeriod($employee);

        $result = (new PayrollCalculator())->calculate($employee, $employee->client, $period, 30);

        $this->assertSame(0.0, $result['pension_employee']);
        $this->assertSame(0.0, $result['pension_employer']);
        $this->assertSame(0.0, $result['fsp_employee']);
        // Salud y el resto de conceptos no se ven afectados por la exención de pensión.
        $this->assertGreaterThan(0, $result['health_employee']);
    }
}
