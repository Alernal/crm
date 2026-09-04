<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeVacationPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recorre el flujo real del control de vacaciones a través de la pila HTTP
 * completa (rutas -> controlador -> Blade -> BD): índice, detalle, registro
 * de saldo inicial, CRUD de períodos, calendario (vista + feed JSON),
 * sugerencia de días hábiles, PDF e impresión.
 */
class VacationControlFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(User $user, Client $client, array $overrides = []): Employee
    {
        return Employee::create(array_merge([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'first_name' => 'Valentina',
            'last_name' => 'Torres',
            'document_type' => 'CC',
            'document_number' => '1044556677',
            'position' => 'Diseñadora UX',
            'contract_type' => 'indefinido',
            'hire_date' => '2022-01-16',
            'base_salary' => 3200000,
            'salary_type' => 'fijo',
            'arl_risk_level' => 'I',
            'status' => 'active',
        ], $overrides));
    }

    public function test_full_vacation_control_flow(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Vacaciones S.A.S.',
            'document_number' => '900777888',
            'dv' => '4',
        ]);
        $employee = $this->makeEmployee($user, $client);

        $this->actingAs($user)->get(route('vacation-control.index'))
            ->assertOk()
            ->assertSee($employee->full_name);

        $this->actingAs($user)->get(route('vacation-control.show', $employee))
            ->assertOk()
            ->assertSee('Saldo de vacaciones');

        // Actualizar saldo inicial.
        $this->actingAs($user)->patch(route('vacation-control.opening-balance.update', $employee), [
            'vacation_opening_balance_days' => 5,
            'vacation_opening_balance_date' => '2025-01-16',
        ])->assertRedirect(route('vacation-control.show', $employee));
        $this->assertEquals(5, $employee->fresh()->vacation_opening_balance_days);

        // Sugerencia de días hábiles (Lunes 4 a domingo 10 de mayo de 2026 -> 5 días hábiles).
        $suggestion = $this->actingAs($user)->getJson(route('vacation-control.suggest-business-days', [
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-10',
        ]));
        $suggestion->assertOk();
        $suggestion->assertJson(['business_days' => 5]);

        // Registrar un período.
        $store = $this->actingAs($user)->post(route('vacation-control.periods.store', $employee), [
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-10',
            'business_days' => 5,
            'notes' => 'Vacaciones de mitad de año',
        ]);
        $store->assertRedirect(route('vacation-control.show', $employee));
        $period = EmployeeVacationPeriod::firstOrFail();
        $this->assertSame($employee->id, $period->employee_id);

        $this->actingAs($user)->get(route('vacation-control.show', $employee))
            ->assertOk()
            ->assertSee('Vacaciones de mitad de año');

        // Editar el período.
        $update = $this->actingAs($user)->patch(route('vacation-control.periods.update', $period), [
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-11',
            'business_days' => 6,
            'notes' => 'Ajustado',
        ]);
        $update->assertRedirect(route('vacation-control.show', $employee));
        $this->assertEquals(6, $period->fresh()->business_days);

        // Calendario: vista + feed de eventos.
        $this->actingAs($user)->get(route('vacation-control.calendar', ['client_id' => $client->id]))
            ->assertOk()
            ->assertSee('Cronograma de vacaciones');

        $events = $this->actingAs($user)->getJson(route('vacation-control.calendar.events', [
            'client_id' => $client->id,
            'start' => '2026-05-01',
            'end' => '2026-05-31',
        ]));
        $events->assertOk();
        $eventIds = collect($events->json())->pluck('id');
        $this->assertTrue($eventIds->contains('period-'.$period->id));

        // PDF e impresión.
        $this->actingAs($user)->get(route('vacation-control.pdf', $employee))->assertOk();
        $this->actingAs($user)->get(route('vacation-control.print', $employee))->assertOk();

        // Eliminar el período.
        $destroy = $this->actingAs($user)->delete(route('vacation-control.periods.destroy', $period));
        $destroy->assertRedirect(route('vacation-control.show', $employee));
        $this->assertDatabaseMissing('employee_vacation_periods', ['id' => $period->id]);
    }

    public function test_vacation_data_is_isolated_between_users(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $client = Client::create([
            'user_id' => $owner->id,
            'name' => 'Cliente Aislado Vacaciones S.A.S.',
            'document_number' => '900777889',
            'dv' => '4',
        ]);
        $employee = $this->makeEmployee($owner, $client);

        $period = EmployeeVacationPeriod::create([
            'user_id' => $owner->id,
            'client_id' => $client->id,
            'employee_id' => $employee->id,
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-10',
            'business_days' => 5,
        ]);

        $this->actingAs($intruder)->get(route('vacation-control.show', $employee))->assertForbidden();
        $this->actingAs($intruder)->get(route('vacation-control.pdf', $employee))->assertForbidden();
        $this->actingAs($intruder)->patch(route('vacation-control.periods.update', $period), [
            'start_date' => '2026-05-04', 'end_date' => '2026-05-10', 'business_days' => 5,
        ])->assertForbidden();
        $this->actingAs($intruder)->delete(route('vacation-control.periods.destroy', $period))->assertForbidden();
    }

    public function test_index_only_lists_active_employees(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Estados S.A.S.',
            'document_number' => '900777890',
            'dv' => '4',
        ]);
        $active = $this->makeEmployee($user, $client, ['status' => 'active', 'document_number' => '1000000001']);
        $inactive = $this->makeEmployee($user, $client, [
            'status' => 'inactive',
            'document_number' => '1000000002',
            'first_name' => 'Retirado',
            'termination_date' => '2025-01-01',
            'termination_reason' => 'renuncia',
        ]);

        $response = $this->actingAs($user)->get(route('vacation-control.index'));

        $response->assertOk();
        $response->assertSee($active->full_name);
        $response->assertDontSee($inactive->full_name);
    }
}
