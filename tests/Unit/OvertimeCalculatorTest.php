<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Employee;
use App\Models\User;
use App\Services\Payroll\OvertimeCalculator;
use Database\Seeders\PayrollLegalSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valida contra "Empleado 3" de storage/app/Nómina/7. Horas extra y recargos
 * mensuales o quincenales.xlsm (hoja "Horas extra", fila 16): salario
 * 2.000.000, jornada 44h/semana, valor hora ordinaria $9.090,91 (44/6*30=220
 * horas mes; 2.000.000/220).
 */
class OvertimeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_ordinary_hourly_rate_matches_excel_reference(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Empleado 3 SAS', 'document_number' => '3333333']);
        $employee = Employee::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'first_name' => 'Empleado',
            'last_name' => '3',
            'document_number' => '3333333',
            'hire_date' => '2020-01-01',
            'base_salary' => 2000000,
            'salary_type' => 'fijo',
        ]);

        $settings = \App\Models\PayrollLegalSetting::forDate(now());
        $calculator = new OvertimeCalculator();

        $this->assertEqualsWithDelta(220.0, $calculator->monthlyHours($employee, $settings), 0.01);
        $this->assertEqualsWithDelta(9090.91, $calculator->ordinaryHourlyRate($employee, $settings), 0.01);

        // Hora extra diurna: 4h a factor 1.25 → total esperado 45.454,55 (Excel: 45454.545...)
        $line = $calculator->lineTotal('extra_diurna', 4, $employee, $settings);
        $this->assertEqualsWithDelta(11363.64, $line['hourly_rate'], 0.02);
        $this->assertEqualsWithDelta(45454.55, $line['total'], 0.05);

        // Hora extra nocturna dominical o festiva: 7h a factor 2.55 → total esperado 162.272,73
        $line = $calculator->lineTotal('extra_nocturna_dominical_festivo', 7, $employee, $settings);
        $this->assertEqualsWithDelta(23181.82, $line['hourly_rate'], 0.02);
        $this->assertEqualsWithDelta(162272.73, $line['total'], 0.1);
    }
}
