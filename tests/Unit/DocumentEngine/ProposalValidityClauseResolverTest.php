<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\User;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use App\Services\DocumentEngine\Resolvers\ProposalValidityClauseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalValidityClauseResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolvableClause(): ResolvableClause
    {
        return new ResolvableClause(
            clauseBlockKey: 'propuesta_validez_aceptacion',
            title: '',
            rawContent: 'x',
            isRequired: true,
            isEditable: true,
            position: 0,
        );
    }

    public function test_adds_business_days_skipping_weekend(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123']);

        // 2026-01-15 es jueves. +3 días hábiles: vie 16, (sáb/dom saltados), lun 19, mar 20.
        $context = new PlaceholderContext($client, $user, [
            'validez' => ['fecha_elaboracion_iso' => '2026-01-15', 'dias' => 3],
        ]);

        $enriched = (new ProposalValidityClauseResolver())->enrich($this->resolvableClause(), $context);

        $this->assertSame('20 de enero de 2026', $enriched->variables['fecha_vencimiento']);
    }
}
