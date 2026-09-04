<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\Payroll\ContractSettlementCalculator;
use Database\Seeders\PayrollLegalSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Valida el motor de cálculo contra los valores exactos del liquidador de
 * Actualícese (Documentos/VA26-Liquidacion-al-terminar-el-contrato.xlsm,
 * hojas "Liquidación al terminar contrato" e "Indemnización") para el caso
 * de ejemplo: contrato indefinido, retiro sin justa causa, salario
 * 2.848.000, terminación el 30-abr-2026, ingreso el 16-ene-2024.
 */
class ContractSettlementCalculatorTest extends TestCase
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
        ]);

        return Employee::create(array_merge([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'first_name' => 'Carlos',
            'last_name' => 'Pérez',
            'document_number' => '77777777',
            'position' => 'Analista contable',
            'contract_type' => 'indefinido',
            'hire_date' => '2024-01-16',
            'termination_date' => '2026-04-30',
            'termination_reason' => 'despido_sin_justa_causa',
            'base_salary' => 2848000,
            'salary_type' => 'fijo',
            'arl_risk_level' => 'I',
        ], $overrides));
    }

    private function excelData(array $overrides = []): array
    {
        return array_merge([
            'smlv' => 1750905,
            'last_salary' => 2848000,
            'basic_salary' => 2848000,
            'transport_allowance_input' => 249095,
            'worked_days_month' => 30,
            'indemnification_applies' => true,
            'contract_type' => 'indefinido',
            'year_start_date' => Carbon::parse('2026-01-01'),
            'vacation_period_start' => Carbon::parse('2026-01-16'),
            'prima_period_start' => Carbon::parse('2026-01-01'),
            'contract_end_date' => Carbon::parse('2026-04-30'),
            'contract_reference_date' => Carbon::parse('2024-01-16'),
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
        ], $overrides);
    }

    public function test_termination_settlement_matches_excel_reference(): void
    {
        (new PayrollLegalSettingSeeder())->run();
        $employee = $this->makeEmployee();

        $result = (new ContractSettlementCalculator())->calculate($employee, $this->excelData());

        $this->assertSame(120.0, $result['prima_days']);
        $this->assertEqualsWithDelta(1032365.0, $result['prima_value'], 0.5);
        $this->assertSame(120.0, $result['cesantias_days']);
        $this->assertEqualsWithDelta(1032365.0, $result['cesantias_value'], 0.5);
        $this->assertEqualsWithDelta(41294.6, $result['interest_cesantias_value'], 0.5);
        $this->assertSame(105.0, $result['vacation_days']);
        $this->assertEqualsWithDelta(415333.33, $result['vacation_value'], 0.5);

        $this->assertEqualsWithDelta(3476000.0, $result['ibc_salarial'], 0.5);
        $this->assertEqualsWithDelta(3189095.0, $result['ibc_no_salarial'], 0.5);
        $this->assertEqualsWithDelta(523057.0, $result['ibc_excess'], 0.5);
        $this->assertEqualsWithDelta(3999057.0, $result['ibc'], 0.5);

        $this->assertEqualsWithDelta(159962.28, $result['health_employee'], 0.5);
        $this->assertEqualsWithDelta(159962.28, $result['pension_employee'], 0.5);
        $this->assertEqualsWithDelta(0.0, $result['fsp_employee'], 0.5);

        $this->assertEqualsWithDelta(5298060.27, $result['indemnification_value'], 0.5);

        $this->assertEqualsWithDelta(14484513.21, $result['total_to_pay'], 0.5);
        $this->assertEqualsWithDelta(624924.56, $result['total_deductions'], 0.5);
        $this->assertEqualsWithDelta(13859588.65, $result['net_pay'], 0.5);
    }

    public function test_no_indemnification_when_it_does_not_apply(): void
    {
        (new PayrollLegalSettingSeeder())->run();
        $employee = $this->makeEmployee(['termination_reason' => 'renuncia']);

        $result = (new ContractSettlementCalculator())->calculate(
            $employee,
            $this->excelData(['indemnification_applies' => false])
        );

        $this->assertSame(0.0, $result['indemnification_value']);
    }

    public function test_fixed_term_indemnification_uses_days_pending_on_agreed_term(): void
    {
        (new PayrollLegalSettingSeeder())->run();
        $employee = $this->makeEmployee([
            'contract_type' => 'fijo',
            'termination_reason' => 'despido_sin_justa_causa',
        ]);

        $result = (new ContractSettlementCalculator())->calculate($employee, $this->excelData([
            'contract_type' => 'fijo',
            'contract_reference_date' => Carbon::parse('2026-12-31'),
        ]));

        $dailySalary = 2848000 / 30;
        $daysPending = Carbon::parse('2026-04-30')->diffInDays(Carbon::parse('2026-12-31'));
        $this->assertEqualsWithDelta($daysPending * $dailySalary, $result['indemnification_value'], 0.5);
    }

    public function test_fixed_term_indemnification_is_zero_when_agreed_term_already_passed(): void
    {
        (new PayrollLegalSettingSeeder())->run();
        $employee = $this->makeEmployee([
            'contract_type' => 'fijo',
            'termination_reason' => 'despido_sin_justa_causa',
        ]);

        $result = (new ContractSettlementCalculator())->calculate($employee, $this->excelData([
            'contract_type' => 'fijo',
            'contract_reference_date' => Carbon::parse('2026-01-01'),
        ]));

        $this->assertSame(0.0, $result['indemnification_value']);
    }

    public function test_obra_labor_indemnification_is_fifteen_fixed_days(): void
    {
        (new PayrollLegalSettingSeeder())->run();
        $employee = $this->makeEmployee([
            'contract_type' => 'obra_labor',
            'termination_reason' => 'despido_sin_justa_causa',
        ]);

        $result = (new ContractSettlementCalculator())->calculate($employee, $this->excelData([
            'contract_type' => 'obra_labor',
        ]));

        $this->assertEqualsWithDelta(15 * (2848000 / 30), $result['indemnification_value'], 0.5);
    }

    public function test_pension_exempt_employee_has_no_pension_or_fsp_deduction(): void
    {
        (new PayrollLegalSettingSeeder())->run();
        $employee = $this->makeEmployee(['pension_exempt' => true]);

        $result = (new ContractSettlementCalculator())->calculate($employee, $this->excelData());

        $this->assertSame(0.0, $result['pension_employee']);
        $this->assertSame(0.0, $result['fsp_employee']);
    }
}
