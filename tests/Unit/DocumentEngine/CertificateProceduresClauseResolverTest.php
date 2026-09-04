<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\User;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use App\Services\DocumentEngine\Resolvers\CertificateProceduresClauseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateProceduresClauseResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolvableClause(): ResolvableClause
    {
        return new ResolvableClause(
            clauseBlockKey: 'certificado_procedimientos',
            title: '',
            rawContent: 'x',
            isRequired: true,
            isEditable: true,
            position: 0,
        );
    }

    public function test_builds_a_numbered_list(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123', 'person_type' => 'natural']);

        $context = new PlaceholderContext($client, $user, [
            'procedimientos' => ['Revisión del RUT', 'Revisión de extractos bancarios'],
        ]);

        $html = (new CertificateProceduresClauseResolver())->enrich($this->resolvableClause(), $context)->variables['procedimientos_html'];

        $this->assertStringContainsString('<li>Revisión del RUT</li>', $html);
        $this->assertStringContainsString('<li>Revisión de extractos bancarios</li>', $html);
    }

    public function test_ignores_blank_entries(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123', 'person_type' => 'natural']);

        $context = new PlaceholderContext($client, $user, [
            'procedimientos' => ['Válido', '  ', ''],
        ]);

        $html = (new CertificateProceduresClauseResolver())->enrich($this->resolvableClause(), $context)->variables['procedimientos_html'];

        $this->assertSame(1, substr_count($html, '<li>'));
    }

    public function test_entries_are_html_escaped(): void
    {
        $user = User::factory()->create();
        $client = Client::create(['user_id' => $user->id, 'name' => 'Cliente', 'document_type' => 'CC', 'document_number' => '123', 'person_type' => 'natural']);

        $context = new PlaceholderContext($client, $user, [
            'procedimientos' => ['<script>alert(1)</script>'],
        ]);

        $html = (new CertificateProceduresClauseResolver())->enrich($this->resolvableClause(), $context)->variables['procedimientos_html'];

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
