<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\User;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use App\Services\DocumentEngine\Resolvers\PreambleClauseResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cuando EL CLIENTE es persona jurídica, quien firma el contrato es el
 * representante legal, no la empresa — la redacción del preámbulo cambia
 * de estructura completa, no solo de datos.
 */
class PreambleClauseResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolvableClause(): ResolvableClause
    {
        return new ResolvableClause(
            clauseBlockKey: 'preambulo',
            title: '',
            rawContent: 'x',
            isRequired: true,
            isEditable: true,
            position: 0,
        );
    }

    public function test_natural_person_client_gets_the_original_wording(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Juan Pérez',
            'document_type' => 'CC',
            'document_number' => '12345678',
            'person_type' => 'natural',
            'city' => 'Bogotá',
            'address' => 'Calle 1 # 2-3',
        ]);

        $context = new PlaceholderContext($client, $user, []);
        $enriched = (new PreambleClauseResolver())->enrich($this->resolvableClause(), $context);
        $description = $enriched->variables['descripcion_cliente'];

        $this->assertStringContainsString('Juan Pérez, mayor de edad, identificado(a) con cédula de ciudadanía No. 12345678', $description);
        $this->assertStringContainsString('expedida en Bogotá', $description);
        $this->assertStringContainsString('con domicilio en Calle 1 # 2-3', $description);
        $this->assertStringNotContainsString('persona jurídica', $description);
        $this->assertStringNotContainsString('Representante Legal', $description);
    }

    public function test_legal_entity_client_is_represented_by_its_legal_representative(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Franco Cerámicas S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '901822884',
            'dv' => '1',
            'person_type' => 'juridica',
            'address' => 'Calle el cauca',
            'chamber_of_commerce_city' => 'Sincelejo',
            'legal_representative_name' => 'María Gómez',
            'legal_representative_document_type' => 'CC',
            'legal_representative_document_number' => '55667788',
        ]);

        $context = new PlaceholderContext($client, $user, []);
        $enriched = (new PreambleClauseResolver())->enrich($this->resolvableClause(), $context);
        $description = $enriched->variables['descripcion_cliente'];

        $this->assertStringContainsString('Franco Cerámicas S.A.S., persona jurídica constituida conforme a las leyes de la República de Colombia', $description);
        $this->assertStringContainsString('identificada con NIT No. 901822884-1', $description);
        $this->assertStringContainsString('con domicilio en Calle el cauca', $description);
        $this->assertStringContainsString('inscrita en el Registro Mercantil ante la Cámara de Comercio de Sincelejo', $description);
        $this->assertStringContainsString('representada en este acto por María Gómez', $description);
        $this->assertStringContainsString('identificado con cédula de ciudadanía No. 55667788', $description);
        $this->assertStringContainsString('en su calidad de Representante Legal debidamente constituido y autorizado', $description);
    }

    public function test_legal_entity_falls_back_to_city_when_chamber_of_commerce_is_not_set(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Radianza S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900111222',
            'person_type' => 'juridica',
            'city' => 'Medellín',
            'chamber_of_commerce_city' => null,
            'legal_representative_name' => 'Pedro López',
            'legal_representative_document_number' => '11223344',
        ]);

        $context = new PlaceholderContext($client, $user, []);
        $enriched = (new PreambleClauseResolver())->enrich($this->resolvableClause(), $context);

        $this->assertStringContainsString('Cámara de Comercio de Medellín', $enriched->variables['descripcion_cliente']);
    }

    public function test_legal_entity_without_registered_representative_flags_the_gap_visibly(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Empresa Incompleta S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900333444',
            'person_type' => 'juridica',
        ]);

        $context = new PlaceholderContext($client, $user, []);
        $enriched = (new PreambleClauseResolver())->enrich($this->resolvableClause(), $context);

        $this->assertStringContainsString('falta registrar el representante legal', $enriched->variables['descripcion_cliente']);
    }

    public function test_client_name_is_html_escaped(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Juan & <script>alert(1)</script>',
            'document_type' => 'CC',
            'document_number' => '12345678',
            'person_type' => 'natural',
        ]);

        $context = new PlaceholderContext($client, $user, []);
        $enriched = (new PreambleClauseResolver())->enrich($this->resolvableClause(), $context);

        $this->assertStringNotContainsString('<script>', $enriched->variables['descripcion_cliente']);
        $this->assertStringContainsString('&amp;', $enriched->variables['descripcion_cliente']);
    }
}
