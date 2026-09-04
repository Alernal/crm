<?php

namespace Tests\Feature\DocumentEngine;

use App\Mail\CertificateDocumentMail;
use App\Models\Client;
use App\Models\GeneratedDocument;
use App\Models\User;
use Database\Seeders\ClauseBlockSeeder;
use Database\Seeders\ContractTemplateSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Prueba de humo del flujo completo de Certificado de Ingresos: wizard ->
 * generación -> vista de solo lectura -> PDF, contra la plantilla real
 * sembrada (modelo Documentos/VB25-Certificado-contador-ingresos-persona-natural.docx).
 * A diferencia de Contratos/Propuestas, solo se emite para clientes persona
 * natural — varios tests cubren esa restricción específicamente.
 */
class CertificateGenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithTemplate(): User
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(ClauseBlockSeeder::class);

        $user = User::factory()->create([
            'nit' => '900123456-1',
            'city' => 'Bogotá D.C.',
            'address' => 'Calle 1 # 2-3',
            'professional_card_number' => 'TP-12345',
        ]);

        $this->seed(ContractTemplateSeeder::class);

        return $user;
    }

    private function naturalClient(User $user, array $overrides = []): Client
    {
        return Client::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Juan Pérez',
            'document_type' => 'CC',
            'document_number' => '12345678',
            'person_type' => 'natural',
            'email' => 'juan@demo.com',
            'status' => 'active',
        ], $overrides));
    }

    private function legalEntityClient(User $user): Client
    {
        return Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Jurídica S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'person_type' => 'juridica',
            'status' => 'active',
        ]);
    }

    private function wizardPayload(int $clientId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'destinatario' => 'A quien interese',
            'ciudad_destinatario' => null,
            'actividad_economica' => 'Prestación de servicios de consultoría',
            'periodo_inicio' => '2025-01-01',
            'periodo_fin' => '2025-12-31',
            'procedimientos' => [
                'Revisión del RUT',
                'Revisión de extractos bancarios',
            ],
            'resultado_revision' => 'De la revisión realizada a los extractos bancarios se evidencia el ingreso señalado.',
            'ingreso_valor' => 60_000_000,
            'ingreso_periodicidad' => 'anual',
            'grupo_niif' => 'no_aplica',
            'ciudad_expedicion' => 'Bogotá D.C.',
            'fecha_expedicion' => '2026-01-15',
        ], $overrides);
    }

    public function test_wizard_page_loads_for_authenticated_user(): void
    {
        $user = $this->makeUserWithTemplate();
        $this->naturalClient($user);

        $response = $this->actingAs($user)->get(route('documents.certificates.wizard'));

        $response->assertOk();
        $response->assertSee('Nuevo Certificado de Ingresos');
    }

    public function test_wizard_only_lists_natural_person_clients(): void
    {
        $user = $this->makeUserWithTemplate();
        $this->naturalClient($user, ['name' => 'Juan Pérez']);
        $this->legalEntityClient($user);

        $response = $this->actingAs($user)->get(route('documents.certificates.wizard'));

        $response->assertSee('Juan Pérez');
        $response->assertDontSee('Cliente Jurídica S.A.S.');
    }

    public function test_store_rejects_a_legal_entity_client_even_if_forced_by_id(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->legalEntityClient($user);

        $response = $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));

        $response->assertStatus(422);
        $this->assertNull(GeneratedDocument::first());
    }

    public function test_store_requires_periodo_fin_after_periodo_inicio(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user);

        $response = $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id, [
            'periodo_inicio' => '2025-12-31',
            'periodo_fin' => '2025-01-01',
        ]));

        $response->assertSessionHasErrors('periodo_fin');
    }

    public function test_generates_a_document_and_redirects_to_show(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user);

        $response = $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));

        $document = GeneratedDocument::first();
        $this->assertNotNull($document);
        $this->assertSame('001-'.now()->year, $document->full_number);
        $this->assertSame('borrador', $document->status);
        $this->assertSame($client->id, $document->client_id);
        $this->assertSame('certificado_ingresos', $document->documentType->key);

        $response->assertRedirect(route('documents.certificates.show', $document));
    }

    public function test_generated_document_resolves_all_clauses_without_leftover_placeholders(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user);

        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));

        $document = GeneratedDocument::with('currentVersion')->firstOrFail();
        $html = $document->currentVersion->content_html;

        $this->assertStringNotContainsString('{{', $html);
        $this->assertCount(7, $document->currentVersion->clauses_data);
        $this->assertStringContainsString('Juan Pérez', $html);
        $this->assertStringContainsString('Revisión del RUT', $html);
        $this->assertStringContainsString('SESENTA MILLONES PESOS M/CTE', $html);
        $this->assertStringContainsString('del 1 de enero de 2025 al 31 de diciembre de 2025', $html);
    }

    public function test_grupo_niif_sentence_only_appears_when_selected(): void
    {
        $user = $this->makeUserWithTemplate();

        $client1 = $this->naturalClient($user, ['document_number' => '11111111']);
        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client1->id, ['grupo_niif' => 'no_aplica']));
        $html1 = GeneratedDocument::with('currentVersion')->firstOrFail()->currentVersion->content_html;
        $this->assertStringNotContainsString('Grupo', $html1);

        $client2 = $this->naturalClient($user, ['document_number' => '22222222', 'name' => 'María Gómez']);
        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client2->id, ['grupo_niif' => '2']));
        $html2 = GeneratedDocument::with('currentVersion')->where('client_id', $client2->id)->firstOrFail()->currentVersion->content_html;
        $this->assertStringContainsString('María Gómez pertenece al Grupo 2 de NIIF', $html2);
    }

    public function test_show_page_renders_the_generated_document(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user);
        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->get(route('documents.certificates.show', $document));

        $response->assertOk();
        $response->assertSee($document->full_number);
        $response->assertSee('Juan Pérez');
    }

    public function test_pdf_downloads_successfully(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user);
        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->get(route('documents.certificates.pdf', $document));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertSame(1, $document->auditLogs()->where('event', 'downloaded_pdf')->count());
    }

    public function test_index_does_not_leak_another_users_certificates(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user);
        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));

        $intruder = User::factory()->create();
        $response = $this->actingAs($intruder)->get(route('documents.certificates.index'));

        $response->assertOk();
        $response->assertDontSee($client->name);
    }

    public function test_index_only_lists_income_certificates_not_contracts_or_proposals(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user, ['name' => 'Solo Certificado']);
        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));

        $contractClient = $this->naturalClient($user, ['document_number' => '99999999', 'name' => 'Solo Contrato']);
        $this->actingAs($user)->post(route('documents.contracts.generate'), [
            'client_id' => $contractClient->id,
            'especialidad' => 'tributaria',
            'ciudad_celebracion' => 'Bogotá D.C.',
            'fecha_elaboracion' => '2026-01-15',
            'servicios' => [['nombre' => 'Asesoría tributaria integral', 'descripcion' => null]],
            'duracion_modo' => 'meses',
            'duracion_meses' => 6,
            'fecha_inicio' => '2026-02-01',
            'valor' => 12_000_000,
            'periodicidad' => 'mensual',
            'valor_periodico' => 2_000_000,
        ]);

        $response = $this->actingAs($user)->get(route('documents.certificates.index'));

        // "Solo Contrato" sí puede aparecer en el <select> de filtro por cliente (lista
        // todos los clientes persona natural, tengan o no un certificado) — lo que NO
        // debe aparecer es el contrato mismo en el listado de documentos.
        $response->assertOk();
        $response->assertSee('Solo Certificado');
        $response->assertSeeInOrder(['CI ', '001-'.now()->year]);
        $response->assertDontSee('CT '.'001-'.now()->year);
    }

    public function test_another_users_document_is_not_visible(): void
    {
        $owner = $this->makeUserWithTemplate();
        $intruder = User::factory()->create();
        $client = $this->naturalClient($owner);
        $this->actingAs($owner)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($intruder)->get(route('documents.certificates.show', $document));

        $response->assertForbidden();
    }

    public function test_sends_document_by_email_and_logs_audit_event(): void
    {
        Mail::fake();
        $user = $this->makeUserWithTemplate();
        $client = $this->naturalClient($user);
        $this->actingAs($user)->post(route('documents.certificates.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->post(route('documents.certificates.send_email', $document), [
            'email' => 'destinatario@example.com',
            'message' => 'Adjunto el certificado solicitado.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Mail::assertSent(CertificateDocumentMail::class, function (CertificateDocumentMail $mail) use ($document) {
            return $mail->hasTo('destinatario@example.com')
                && $mail->document->id === $document->id
                && $mail->customMessage === 'Adjunto el certificado solicitado.';
        });

        $log = $document->auditLogs()->where('event', 'emailed')->first();
        $this->assertNotNull($log);
        $this->assertSame('destinatario@example.com', $log->meta['to']);
    }
}
