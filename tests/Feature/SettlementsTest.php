<?php

namespace Tests\Feature;

use App\Models\CesantiaSettlement;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PrimaSettlement;
use App\Models\User;
use Database\Seeders\PayrollLegalSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SettlementsTest extends TestCase
{
    use RefreshDatabase;

    private function generateMonthlyPeriods(User $user, Client $client, int $year, int $fromMonth, int $toMonth): void
    {
        for ($month = $fromMonth; $month <= $toMonth; $month++) {
            $start = Carbon::create($year, $month, 1);
            $end = (clone $start)->endOfMonth();

            $this->actingAs($user)->post(route('payroll-periods.store'), [
                'client_id' => $client->id,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'payment_date' => $end->format('Y-m-d'),
            ])->assertRedirect();
        }
    }

    public function test_prima_settlement_sums_provisions_across_semester(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);
        $user = User::factory()->create();

        $client = Client::create([
            'user_id' => $user->id, 'name' => 'Prima Test S.A.S.', 'document_number' => '900111222',
            'payroll_periodicity' => 'mensual', 'payroll_prefix' => 'NOM',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id, 'client_id' => $client->id,
            'first_name' => 'Laura', 'last_name' => 'Perez', 'document_number' => '444555666',
            'hire_date' => '2020-01-01', 'base_salary' => 2000000, 'salary_type' => 'fijo',
        ]);

        $this->generateMonthlyPeriods($user, $client, 2026, 1, 6);

        $expectedPrima = (float) Payroll::whereHas('payrollPeriod', fn ($q) => $q->where('client_id', $client->id))->sum('prima_provision');
        $this->assertGreaterThan(0, $expectedPrima);

        $storeResponse = $this->actingAs($user)->post(route('prima-settlements.store'), [
            'client_id' => $client->id,
            'year' => 2026,
            'semester' => 1,
            'payment_date' => '2026-06-30',
        ]);
        $storeResponse->assertRedirect();

        $settlement = PrimaSettlement::where('client_id', $client->id)->firstOrFail();
        $this->assertEqualsWithDelta($expectedPrima, $settlement->total_prima, 0.01);

        // No permite duplicar la liquidación del mismo cliente/año/semestre.
        $this->actingAs($user)->post(route('prima-settlements.store'), [
            'client_id' => $client->id,
            'year' => 2026,
            'semester' => 1,
            'payment_date' => '2026-06-30',
        ])->assertStatus(422);

        $showResponse = $this->actingAs($user)->get(route('prima-settlements.show', $settlement));
        $showResponse->assertOk();
        $showResponse->assertSee($employee->full_name);

        $this->actingAs($user)->get(route('prima-settlements.pdf', $settlement))->assertOk();
        $this->actingAs($user)->get(route('prima-settlements.print', $settlement))->assertOk();

        $this->actingAs($user)->delete(route('prima-settlements.destroy', $settlement))->assertRedirect();
        $this->assertDatabaseMissing('prima_settlements', ['id' => $settlement->id]);
    }

    public function test_cesantia_settlement_sums_provisions_across_year(): void
    {
        $this->seed(PayrollLegalSettingSeeder::class);
        $user = User::factory()->create();

        $client = Client::create([
            'user_id' => $user->id, 'name' => 'Cesantias Test S.A.S.', 'document_number' => '900333444',
            'payroll_periodicity' => 'mensual', 'payroll_prefix' => 'NOM',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id, 'client_id' => $client->id,
            'first_name' => 'Jorge', 'last_name' => 'Diaz', 'document_number' => '777888999',
            'hire_date' => '2020-01-01', 'base_salary' => 2000000, 'salary_type' => 'fijo',
        ]);

        $this->generateMonthlyPeriods($user, $client, 2026, 1, 12);

        $expectedCesantias = (float) Payroll::whereHas('payrollPeriod', fn ($q) => $q->where('client_id', $client->id))->sum('cesantias_provision');
        $expectedInterest = (float) Payroll::whereHas('payrollPeriod', fn ($q) => $q->where('client_id', $client->id))->sum('interest_cesantias_provision');
        $this->assertGreaterThan(0, $expectedCesantias);
        $this->assertGreaterThan(0, $expectedInterest);

        $storeResponse = $this->actingAs($user)->post(route('cesantia-settlements.store'), [
            'client_id' => $client->id,
            'year' => 2026,
            'payment_date' => '2027-02-14',
        ]);
        $storeResponse->assertRedirect();

        $settlement = CesantiaSettlement::where('client_id', $client->id)->firstOrFail();
        $this->assertEqualsWithDelta($expectedCesantias, $settlement->total_cesantias, 0.01);
        $this->assertEqualsWithDelta($expectedInterest, $settlement->total_interest, 0.01);

        $this->actingAs($user)->post(route('cesantia-settlements.store'), [
            'client_id' => $client->id,
            'year' => 2026,
            'payment_date' => '2027-02-14',
        ])->assertStatus(422);

        $showResponse = $this->actingAs($user)->get(route('cesantia-settlements.show', $settlement));
        $showResponse->assertOk();
        $showResponse->assertSee($employee->full_name);

        $this->actingAs($user)->get(route('cesantia-settlements.pdf', $settlement))->assertOk();
        $this->actingAs($user)->get(route('cesantia-settlements.print', $settlement))->assertOk();

        $this->actingAs($user)->delete(route('cesantia-settlements.destroy', $settlement))->assertRedirect();
        $this->assertDatabaseMissing('cesantia_settlements', ['id' => $settlement->id]);
    }
}
