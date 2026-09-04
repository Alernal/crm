<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeVacationPeriod;
use App\Models\User;
use App\Services\Payroll\VacationBalanceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class VacationBalanceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(array $overrides = []): Employee
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Vacaciones S.A.S.',
            'document_number' => '900111222',
            'dv' => '3',
        ]);

        return Employee::create(array_merge([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'first_name' => 'Sofía',
            'last_name' => 'Ramírez',
            'document_number' => '98765432',
            'position' => 'Diseñadora',
            'contract_type' => 'indefinido',
            'hire_date' => '2022-01-16',
            'base_salary' => 3000000,
            'salary_type' => 'fijo',
            'arl_risk_level' => 'I',
        ], $overrides));
    }

    public function test_accrues_fifteen_days_per_completed_year_since_hire_date(): void
    {
        $employee = $this->makeEmployee();

        $result = (new VacationBalanceCalculator())->calculate($employee, new Collection(), Carbon::parse('2026-04-30'));

        // 2022-01-16 -> 2026-04-30: 4 años completos (el 5º empieza 2026-01-16 y aún no cumple).
        $this->assertSame(4, $result['accrued_years']);
        $this->assertSame(60.0, $result['accrued_days']);
        $this->assertSame(0.0, $result['taken_days_since_opening']);
        $this->assertSame(60.0, $result['pending_balance']);
        $this->assertTrue($result['next_accrual_date']->isSameDay(Carbon::parse('2027-01-16')));
    }

    public function test_subtracts_taken_periods_since_opening_date(): void
    {
        $employee = $this->makeEmployee();

        $periods = new Collection([
            EmployeeVacationPeriod::make(['start_date' => '2023-05-04', 'end_date' => '2023-05-10', 'business_days' => 5]),
            EmployeeVacationPeriod::make(['start_date' => '2025-01-05', 'end_date' => '2025-01-07', 'business_days' => 3]),
        ]);

        $result = (new VacationBalanceCalculator())->calculate($employee, $periods, Carbon::parse('2026-04-30'));

        $this->assertSame(8.0, $result['taken_days_since_opening']);
        $this->assertSame(52.0, $result['pending_balance']); // 60 acumulados - 8 tomados
    }

    public function test_manual_opening_balance_overrides_hire_date_accrual(): void
    {
        // Empleado con historial previo al módulo: saldo conocido de 5 días
        // a una fecha de referencia posterior al ingreso — no debe acumular
        // desde el ingreso real, sino desde esa fecha de referencia.
        $employee = $this->makeEmployee([
            'vacation_opening_balance_days' => 5,
            'vacation_opening_balance_date' => '2025-01-16',
        ]);

        $result = (new VacationBalanceCalculator())->calculate($employee, new Collection(), Carbon::parse('2026-04-30'));

        $this->assertSame(1, $result['accrued_years']); // 2025-01-16 -> 2026-04-30: 1 año cumplido
        $this->assertSame(20.0, $result['pending_balance']); // 5 iniciales + 15 acumulados
    }

    public function test_taken_periods_before_opening_date_do_not_count(): void
    {
        $employee = $this->makeEmployee([
            'vacation_opening_balance_days' => 5,
            'vacation_opening_balance_date' => '2025-01-16',
        ]);

        $periods = new Collection([
            // Anterior a la fecha de referencia: ya está reflejado en el saldo inicial, no se resta de nuevo.
            EmployeeVacationPeriod::make(['start_date' => '2023-05-04', 'end_date' => '2023-05-10', 'business_days' => 5]),
        ]);

        $result = (new VacationBalanceCalculator())->calculate($employee, $periods, Carbon::parse('2026-04-30'));

        $this->assertSame(0.0, $result['taken_days_since_opening']);
        $this->assertSame(20.0, $result['pending_balance']);
    }

    public function test_complies_minimum_current_year_checks_six_days_taken_this_calendar_year(): void
    {
        $employee = $this->makeEmployee();
        Carbon::setTestNow('2026-06-01');

        $periodsCompliant = new Collection([
            EmployeeVacationPeriod::make(['start_date' => '2026-03-02', 'end_date' => '2026-03-10', 'business_days' => 7]),
        ]);
        $resultCompliant = (new VacationBalanceCalculator())->calculate($employee, $periodsCompliant);
        $this->assertTrue($resultCompliant['complies_minimum_current_year']);

        $periodsNonCompliant = new Collection([
            EmployeeVacationPeriod::make(['start_date' => '2026-03-02', 'end_date' => '2026-03-05', 'business_days' => 4]),
        ]);
        $resultNonCompliant = (new VacationBalanceCalculator())->calculate($employee, $periodsNonCompliant);
        $this->assertFalse($resultNonCompliant['complies_minimum_current_year']);

        Carbon::setTestNow();
    }

    public function test_stops_accruing_after_termination_date(): void
    {
        $employee = $this->makeEmployee(['termination_date' => '2025-06-01']);

        $result = (new VacationBalanceCalculator())->calculate($employee, new Collection(), Carbon::parse('2026-04-30'));

        // Aunque "hoy" sea 2026-04-30, el retiro fue 2025-06-01: solo 3 años cumplidos (2025-01-16 no, ingreso 2022-01-16 -> 2025-01-16 son 3 años).
        $this->assertSame(3, $result['accrued_years']);
    }

    public function test_suggested_business_days_excludes_weekends_and_colombian_holidays(): void
    {
        $calculator = new VacationBalanceCalculator();

        // Lunes 4 a domingo 10 de mayo de 2026, sin festivos en el rango: 5 días hábiles (L-V).
        $this->assertSame(5.0, $calculator->suggestedBusinessDays(Carbon::parse('2026-05-04'), Carbon::parse('2026-05-10')));

        // 1 al 6 de abril de 2026 incluye Jueves (2) y Viernes Santo (3): de 6 días calendario, solo 2 son hábiles (miércoles 1 y lunes 6).
        $this->assertSame(2.0, $calculator->suggestedBusinessDays(Carbon::parse('2026-04-01'), Carbon::parse('2026-04-06')));
    }
}
