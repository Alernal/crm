<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_data(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente Uno', 'document_number' => '900111222', 'status' => 'active']);

        $invoice1 = Invoice::create([
            'user_id' => $user->id, 'client_id' => $client->id, 'number' => 'CC-0001',
            'issue_date' => now()->subMonths(2), 'due_date' => now()->subMonths(2)->addDays(30),
            'subtotal' => 1000000, 'vat_amount' => 190000, 'total' => 1190000, 'status' => 'paid',
        ]);
        Payment::create(['invoice_id' => $invoice1->id, 'user_id' => $user->id, 'amount' => 1190000, 'payment_date' => now()->subMonths(2)->addDays(10)]);

        Invoice::create([
            'user_id' => $user->id, 'client_id' => $client->id, 'number' => 'CC-0002',
            'issue_date' => now()->subDays(70), 'due_date' => now()->subDays(40),
            'subtotal' => 500000, 'vat_amount' => 95000, 'total' => 595000, 'status' => 'sent',
        ]);

        Invoice::create([
            'user_id' => $user->id, 'client_id' => $client->id, 'number' => 'CC-0003',
            'issue_date' => now()->subDays(5), 'due_date' => now()->addDays(25),
            'subtotal' => 300000, 'vat_amount' => 57000, 'total' => 357000, 'status' => 'sent',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Cartera por antigüedad');
        $response->assertSee('Facturado vs. cobrado');
        $response->assertSee('agingChart', false);
        $response->assertSee('trendChart', false);
    }

    public function test_dashboard_renders_with_no_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Sin cartera pendiente por analizar');
    }
}
