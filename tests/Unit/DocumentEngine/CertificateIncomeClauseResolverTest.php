<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\User;
use App\Services\DocumentEngine\NumberToWordsService;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use App\Services\DocumentEngine\Resolvers\CertificateIncomeClauseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateIncomeClauseResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolvableClause(): ResolvableClause
    {
        return new ResolvableClause(
            clauseBlockKey: 'certificado_certificacion',
            title: '',
            rawContent: 'x',
            isRequired: true,
            isEditable: true,
            position: 0,
        );
    }

    private function resolver(): CertificateIncomeClauseResolver
    {
        return new CertificateIncomeClauseResolver(new NumberToWordsService());
    }

    private function client(User $user): Client
    {
        return Client::create([
            'user_id' => $user->id,
            'name' => 'Juan Pérez',
            'document_type' => 'CC',
            'document_number' => '123',
            'person_type' => 'natural',
        ]);
    }

    public function test_computes_periodo_texto_and_valor_letras(): void
    {
        $user = User::factory()->create();
        $context = new PlaceholderContext($this->client($user), $user, [
            'periodo' => ['fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-12-31'],
            'ingreso' => ['valor' => 60_000_000, 'periodicidad' => 'anual'],
            'grupo_niif' => 'no_aplica',
        ]);

        $vars = $this->resolver()->enrich($this->resolvableClause(), $context)->variables;

        $this->assertSame('del 1 de enero de 2025 al 31 de diciembre de 2025', $vars['periodo_texto']);
        $this->assertSame('SESENTA MILLONES PESOS M/CTE', $vars['ingreso_valor_letras']);
        $this->assertSame('60.000.000', $vars['ingreso_valor_formateado']);
        $this->assertSame('anual', $vars['ingreso_periodicidad_texto']);
        $this->assertSame('', $vars['grupo_niif_texto']);
    }

    public function test_grupo_niif_sentence_names_the_client_when_selected(): void
    {
        $user = User::factory()->create();
        $context = new PlaceholderContext($this->client($user), $user, [
            'periodo' => ['fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-12-31'],
            'ingreso' => ['valor' => 1000, 'periodicidad' => 'mensual'],
            'grupo_niif' => '2',
        ]);

        $vars = $this->resolver()->enrich($this->resolvableClause(), $context)->variables;

        $this->assertStringContainsString('Juan Pérez pertenece al Grupo 2 de NIIF', $vars['grupo_niif_texto']);
    }
}
