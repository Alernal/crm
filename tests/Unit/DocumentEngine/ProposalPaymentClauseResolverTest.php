<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\User;
use App\Services\DocumentEngine\NumberToWordsService;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use App\Services\DocumentEngine\Resolvers\ProposalPaymentClauseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalPaymentClauseResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolvableClause(): ResolvableClause
    {
        return new ResolvableClause(
            clauseBlockKey: 'propuesta_inversion_pago',
            title: '',
            rawContent: 'x',
            isRequired: true,
            isEditable: true,
            position: 0,
        );
    }

    private function resolver(): ProposalPaymentClauseResolver
    {
        return new ProposalPaymentClauseResolver(new NumberToWordsService());
    }

    public function test_unico_forma_pago(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123']);

        $context = new PlaceholderContext($client, $user, [
            'inversion' => ['valor' => 5_000_000, 'forma_pago' => 'unico'],
        ]);

        $html = $this->resolver()->enrich($this->resolvableClause(), $context)->variables['inversion_html'];

        $this->assertStringContainsString('CINCO MILLONES PESOS M/CTE', $html);
        $this->assertStringContainsString('$5.000.000', $html);
        $this->assertStringContainsString('pago único al inicio de los servicios', $html);
    }

    public function test_cuotas_breakdown(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123']);

        $context = new PlaceholderContext($client, $user, [
            'inversion' => [
                'valor' => 4_000_000,
                'forma_pago' => 'cuotas',
                'cuotas' => [
                    ['valor' => 2_000_000, 'vencimiento' => '2026-02-01'],
                    ['valor' => 2_000_000, 'vencimiento' => '2026-03-01'],
                ],
            ],
        ]);

        $html = $this->resolver()->enrich($this->resolvableClause(), $context)->variables['inversion_html'];

        $this->assertStringContainsString('en 2 cuotas', $html);
        $this->assertStringContainsString('Cuota 1: $2.000.000', $html);
        $this->assertStringContainsString('Cuota 2: $2.000.000', $html);
    }

    public function test_condiciones_pago_are_escaped_when_present(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123']);

        $context = new PlaceholderContext($client, $user, [
            'inversion' => ['valor' => 1_000_000, 'forma_pago' => 'unico', 'condiciones_pago' => '<b>x</b>'],
        ]);

        $html = $this->resolver()->enrich($this->resolvableClause(), $context)->variables['inversion_html'];

        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
    }
}
