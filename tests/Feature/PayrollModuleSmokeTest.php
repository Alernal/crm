<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\PayrollLegalSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Recorre el flujo real de nómina a través de la pila HTTP completa
 * (rutas -> controladores -> Blade -> BD), tal como lo haría un navegador,
 * para detectar errores 500 o de sintaxis Blade que un test unitario del
 * motor de cálculo no vería.
 */
class PayrollModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_payroll_flow_end_to_end(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);
        Mail::fake();

        $user = User::factory()->create(['name' => 'Andrés Felipe Contador', 'nit' => '1102866622']);

        // ── Crear cliente con configuración de nómina ──
        $response = $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'QA Nomina S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900123456',
            'person_type' => 'juridica',
            'tax_regime' => 'no_aplica',
            'status' => 'active',
            'payroll_periodicity' => 'mensual',
            'payroll_prefix' => 'NOM',
            'payroll_pila_exempt' => '1',
        ]);
        $response->assertRedirect();
        $client = Client::where('name', 'QA Nomina S.A.S.')->firstOrFail();
        $this->assertEquals('mensual', $client->payroll_periodicity);
        $this->assertTrue((bool) $client->payroll_pila_exempt);

        $this->get(route('clients.edit', $client))->assertOk();

        // ── Crear 2 empleados ──
        $this->actingAs($user)->post(route('employees.store'), [
            'client_id' => $client->id,
            'first_name' => 'Maria',
            'last_name' => 'Gomez',
            'document_type' => 'CC',
            'document_number' => '1000111222',
            'email' => 'maria@qanomina.test',
            'contract_type' => 'indefinido',
            'hire_date' => '2024-01-15',
            'base_salary' => 2000000,
            'salary_type' => 'fijo',
            'transport_allowance_mode' => 'automatico',
            'arl_risk_level' => 'I',
            'status' => 'active',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('employees.store'), [
            'client_id' => $client->id,
            'first_name' => 'Carlos',
            'last_name' => 'Ramirez',
            'document_type' => 'CC',
            'document_number' => '1000333444',
            'contract_type' => 'indefinido',
            'hire_date' => '2023-06-01',
            'base_salary' => 1800000,
            'salary_type' => 'fijo',
            'transport_allowance_mode' => 'nunca',
            'transport_allowance_note' => 'Vive en las instalaciones de la empresa',
            'arl_risk_level' => 'I',
            'status' => 'active',
        ])->assertRedirect();

        $this->assertEquals(2, Employee::where('client_id', $client->id)->count());

        $employeesIndex = $this->actingAs($user)->get(route('employees.index'));
        $employeesIndex->assertOk();
        $employeesIndex->assertSee('Maria Gomez');
        $employeesIndex->assertSee('Carlos Ramirez');

        $maria = Employee::where('document_number', '1000111222')->firstOrFail();
        $this->actingAs($user)->get(route('employees.show', $maria))->assertOk();
        $this->actingAs($user)->get(route('employees.edit', $maria))->assertOk();

        // ── Generar período de nómina ──
        $genResponse = $this->actingAs($user)->post(route('payroll-periods.store'), [
            'client_id' => $client->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-30',
            'payment_date' => '2026-01-30',
        ]);
        $genResponse->assertRedirect();

        $period = PayrollPeriod::where('client_id', $client->id)->firstOrFail();
        $this->assertEquals('NOM-0001', $period->number);
        $this->assertEquals(2, $period->payrolls()->count());

        $showResponse = $this->actingAs($user)->get(route('payroll-periods.show', $period));
        $showResponse->assertOk();
        $showResponse->assertSee('Maria Gomez');
        $showResponse->assertSee('Carlos Ramirez');
        $showResponse->assertSee($period->number);

        $mariaPayroll = Payroll::where('employee_id', $maria->id)->where('payroll_period_id', $period->id)->firstOrFail();
        // Salario fijo, 30 días => auxilio de transporte automático debería aplicar (2.000.000 < 2*smlv).
        $this->assertGreaterThan(0, (float) $mariaPayroll->transport_allowance);

        $carlos = Employee::where('document_number', '1000333444')->firstOrFail();
        $carlosPayroll = Payroll::where('employee_id', $carlos->id)->where('payroll_period_id', $period->id)->firstOrFail();
        // Override "nunca" => nunca debe pagarse auxilio aunque el salario esté bajo el tope.
        $this->assertEquals(0.0, (float) $carlosPayroll->transport_allowance);

        // ── Editar conceptos ──
        $updateResponse = $this->actingAs($user)->patch(route('payrolls.update', $mariaPayroll), [
            'worked_days' => 30,
            'commissions' => 50000,
            'bonuses_salarial' => 30000,
        ]);
        $updateResponse->assertRedirect();
        $mariaPayroll->refresh();
        $this->assertEquals(50000.0, (float) $mariaPayroll->commissions);
        $this->assertEquals(30000.0, (float) $mariaPayroll->bonuses_salarial);
        $earnedAfterConcepts = (float) $mariaPayroll->total_earned;

        // ── Horas extra ──
        $overtimeResponse = $this->actingAs($user)->put(route('payrolls.overtime.update', $mariaPayroll), [
            'items' => [
                ['type' => 'extra_diurna', 'hours' => 4],
                ['type' => 'recargo_nocturno', 'hours' => 2],
            ],
        ]);
        $overtimeResponse->assertRedirect();
        $mariaPayroll->refresh();
        $this->assertEquals(2, $mariaPayroll->overtimeItems()->count());
        $this->assertGreaterThan(0, (float) $mariaPayroll->overtime_pay);
        // El devengado total debe subir tras agregar horas extra.
        $this->assertGreaterThan($earnedAfterConcepts, (float) $mariaPayroll->total_earned);

        // ── PDF / Print / Email ──
        $pdfResponse = $this->actingAs($user)->get(route('payrolls.pdf', $mariaPayroll));
        $pdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('content-type'));

        $printResponse = $this->actingAs($user)->get(route('payrolls.print', $mariaPayroll));
        $printResponse->assertOk();
        $printResponse->assertSee('Comprobante de pago de n', false);
        // El desprendible se emite a nombre del cliente (empleador real), no del contador.
        $printResponse->assertSee('QA Nomina S.A.S.');
        $printResponse->assertSee('900123456');
        $printResponse->assertDontSee('Andrés Felipe Contador');
        $printResponse->assertDontSee('1102866622');

        // ── PDF / Print de la tabla completa del período (resumen: devengo + salud/pensión + neto) ──
        $periodPdfResponse = $this->actingAs($user)->get(route('payroll-periods.pdf', $period));
        $periodPdfResponse->assertOk();
        $this->assertStringContainsString('application/pdf', $periodPdfResponse->headers->get('content-type'));

        $periodPrintResponse = $this->actingAs($user)->get(route('payroll-periods.print', $period));
        $periodPrintResponse->assertOk();
        $periodPrintResponse->assertSee('Maria Gomez');
        $periodPrintResponse->assertSee('Carlos Ramirez');
        // La tabla de nómina también se emite a nombre del cliente, no del contador.
        $periodPrintResponse->assertSee('QA Nomina S.A.S.');
        $periodPrintResponse->assertDontSee('Andrés Felipe Contador');
        $periodPrintResponse->assertDontSee('1102866622');
        $periodPrintResponse->assertSee('Comisiones');
        $periodPrintResponse->assertSee('Total devengado');
        // Salud y pensión del trabajador SÍ deben listarse (excepción pedida explícitamente).
        $periodPrintResponse->assertSee('Salud');
        $periodPrintResponse->assertSee('Pensión');
        $periodPrintResponse->assertSee('Total descuentos');
        $periodPrintResponse->assertSee('Neto a pagar');
        // El resto de la seguridad social y las prestaciones sociales siguen fuera.
        $periodPrintResponse->assertDontSee('Fondo de solidaridad');
        $periodPrintResponse->assertDontSee('Cesantías');
        $periodPrintResponse->assertDontSee('Prima de servicios');

        $emailResponse = $this->actingAs($user)->post(route('payrolls.send_email', $mariaPayroll), [
            'email' => 'maria@qanomina.test',
            'message' => 'Aquí tienes tu desprendible.',
        ]);
        $emailResponse->assertRedirect();
        Mail::assertSent(\App\Mail\PayslipMail::class, function ($mail) use ($mariaPayroll) {
            return $mail->payroll->id === $mariaPayroll->id;
        });

        // ── Procesar período ──
        $closeResponse = $this->actingAs($user)->patch(route('payroll-periods.close', $period));
        $closeResponse->assertRedirect();
        $period->refresh();
        $this->assertEquals('procesada', $period->status);
        $this->assertEquals(2, $period->payrolls()->where('status', 'emitida')->count());

        // Un período procesado todavía se puede corregir (ej. error detectado tras procesar).
        $this->actingAs($user)->patch(route('payrolls.update', $mariaPayroll), ['worked_days' => 30])
            ->assertRedirect();

        // Pero una vez marcado como pagado, ya no se puede editar.
        $period->update(['status' => 'pagada']);
        $this->actingAs($user)->patch(route('payrolls.update', $mariaPayroll), ['worked_days' => 30])
            ->assertStatus(422);
    }

    public function test_employee_and_payroll_are_isolated_between_users(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);

        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $client = Client::create(['user_id' => $owner->id, 'name' => 'Aislado S.A.S.', 'document_number' => '1', 'payroll_periodicity' => 'mensual']);
        $employee = Employee::create([
            'user_id' => $owner->id, 'client_id' => $client->id,
            'first_name' => 'A', 'last_name' => 'B', 'document_number' => '1',
            'hire_date' => '2020-01-01', 'base_salary' => 2000000, 'salary_type' => 'fijo',
        ]);

        $this->actingAs($intruder)->get(route('employees.show', $employee))->assertForbidden();
        $this->actingAs($intruder)->get(route('employees.edit', $employee))->assertForbidden();
    }
}
