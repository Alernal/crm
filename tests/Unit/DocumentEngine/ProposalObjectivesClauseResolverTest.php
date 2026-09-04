<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\User;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use App\Services\DocumentEngine\Resolvers\ProposalObjectivesClauseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalObjectivesClauseResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolvableClause(): ResolvableClause
    {
        return new ResolvableClause(
            clauseBlockKey: 'propuesta_objetivos',
            title: '',
            rawContent: 'x',
            isRequired: true,
            isEditable: true,
            position: 0,
        );
    }

    public function test_builds_general_and_specific_objectives(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123']);

        $context = new PlaceholderContext($client, $user, [
            'objetivos' => [
                'general' => 'Garantizar el cumplimiento tributario.',
                'especificos' => ['Diagnosticar la situación actual', 'Definir un calendario de cumplimiento'],
            ],
        ]);

        $enriched = (new ProposalObjectivesClauseResolver())->enrich($this->resolvableClause(), $context);
        $html = $enriched->variables['objetivos_html'];

        $this->assertStringContainsString('Garantizar el cumplimiento tributario.', $html);
        $this->assertStringContainsString('<li>Diagnosticar la situación actual</li>', $html);
        $this->assertStringContainsString('<li>Definir un calendario de cumplimiento</li>', $html);
    }

    public function test_ignores_blank_specific_objectives(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123']);

        $context = new PlaceholderContext($client, $user, [
            'objetivos' => ['general' => 'Objetivo general.', 'especificos' => ['Válido', '   ', '']],
        ]);

        $enriched = (new ProposalObjectivesClauseResolver())->enrich($this->resolvableClause(), $context);

        $this->assertSame(1, substr_count($enriched->variables['objetivos_html'], '<li>'));
    }

    public function test_specific_objectives_are_html_escaped(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123']);

        $context = new PlaceholderContext($client, $user, [
            'objetivos' => ['general' => 'x', 'especificos' => ['<script>alert(1)</script>']],
        ]);

        $enriched = (new ProposalObjectivesClauseResolver())->enrich($this->resolvableClause(), $context);

        $this->assertStringNotContainsString('<script>', $enriched->variables['objetivos_html']);
        $this->assertStringContainsString('&lt;script&gt;', $enriched->variables['objetivos_html']);
    }
}
