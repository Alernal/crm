<?php

namespace Tests\Feature\DocumentEngine;

use App\Mail\ProposalDocumentMail;
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
 * Prueba de humo del flujo completo de Propuestas: wizard -> generación ->
 * vista de solo lectura -> PDF, contra la plantilla real sembrada
 * (modelo Documentos/PROPUESTA_EJEMPLO_COMPLETO.docx).
 */
class ProposalGenerationFlowTest extends TestCase
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

    private function wizardPayload(int $clientId, array $overrides = []): array
    {
        return array_merge([
            'client_id' => $clientId,
            'especialidad' => 'tributaria',
            'servicios' => [
                ['nombre' => 'Asesoría tributaria integral', 'descripcion' => null],
                ['nombre' => 'Declaraciones periódicas', 'descripcion' => 'IVA, Renta, ICA'],
            ],
            'descripcion_proyecto' => 'El cliente requiere apoyo integral en el cumplimiento de sus obligaciones tributarias.',
            'objetivo_general' => 'Garantizar el cumplimiento oportuno de las obligaciones tributarias del cliente.',
            'objetivos_especificos' => [
                'Diagnosticar la situación tributaria actual',
                'Implementar un calendario de cumplimiento',
            ],
            'metodologia_fase1' => 'Recopilación de información contable y tributaria.',
            'metodologia_fase2' => 'Análisis de la información y de riesgos tributarios.',
            'metodologia_fase3' => 'Entrega de informe con hallazgos y recomendaciones.',
            'ciudad_celebracion' => 'Bogotá D.C.',
            'fecha_elaboracion' => '2026-01-15',
            'validez_dias' => 15,
            'valor' => 12_000_000,
            'forma_pago' => 'unico',
            'condiciones_pago' => 'Pago anticipado mediante transferencia bancaria.',
        ], $overrides);
    }

    private function createClient(User $user, array $overrides = []): Client
    {
        return Client::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'email' => 'cliente@demo.com',
            'status' => 'active',
        ], $overrides));
    }

    public function test_wizard_page_loads_for_authenticated_user(): void
    {
        $user = $this->makeUserWithTemplate();

        $response = $this->actingAs($user)->get(route('documents.proposals.wizard'));

        $response->assertOk();
        $response->assertSee('Nueva Propuesta');
    }

    public function test_store_requires_a_valid_especialidad(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);
        $payload = $this->wizardPayload($client->id);
        unset($payload['especialidad']);

        $response = $this->actingAs($user)->post(route('documents.proposals.generate'), $payload);

        $response->assertSessionHasErrors('especialidad');
    }

    public function test_store_requires_cuotas_when_forma_pago_is_cuotas(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);
        $payload = $this->wizardPayload($client->id, ['forma_pago' => 'cuotas']);

        $response = $this->actingAs($user)->post(route('documents.proposals.generate'), $payload);

        $response->assertSessionHasErrors('cuotas');
    }

    /**
     * Bug reportado: el wizard usaba x-show (no x-if/:disabled) en las filas de cuota, así
     * que un navegador viejo en caché podía enviar cuotas[0] vacío junto con forma_pago=unico
     * y el servidor respondía 422 ("cuotas.0.vencimiento field is required"). La validación
     * ahora depende de forma_pago, no de la sola presencia del array.
     */
    public function test_stray_empty_cuota_row_does_not_fail_when_forma_pago_is_unico(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);
        $payload = $this->wizardPayload($client->id, [
            'forma_pago' => 'unico',
            'cuotas' => [['valor' => 0, 'vencimiento' => '']],
        ]);

        $response = $this->actingAs($user)->post(route('documents.proposals.generate'), $payload);

        $response->assertSessionDoesntHaveErrors();
        $this->assertNotNull(GeneratedDocument::first());
    }

    public function test_generates_a_document_and_redirects_to_show(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user, ['city' => 'Medellín', 'address' => 'Carrera 4 # 5-6']);

        $response = $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id));

        $document = GeneratedDocument::first();
        $this->assertNotNull($document);
        $this->assertSame('001-'.now()->year, $document->full_number);
        $this->assertSame('borrador', $document->status);
        $this->assertSame($client->id, $document->client_id);
        $this->assertNotNull($document->current_version_id);
        $this->assertSame('propuesta_comercial', $document->documentType->key);

        $response->assertRedirect(route('documents.proposals.show', $document));
    }

    public function test_generated_document_resolves_all_clauses_without_leftover_placeholders(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);

        $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id, [
            'objetivos_especificos' => ['Diagnosticar la situación tributaria actual'],
        ]));

        $document = GeneratedDocument::with('currentVersion')->firstOrFail();
        $html = $document->currentVersion->content_html;

        $this->assertStringNotContainsString('{{', $html);
        $this->assertCount(16, $document->currentVersion->clauses_data);
        $this->assertStringContainsString('Asesoría tributaria integral', $html);
        $this->assertStringContainsString('DOCE MILLONES PESOS M/CTE', $html);
        $this->assertStringContainsString('Diagnosticar la situación tributaria actual', $html);
        $this->assertStringContainsString('pago único al inicio de los servicios', $html);
    }

    public function test_cuotas_are_broken_down_in_the_payment_clause(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);

        $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id, [
            'forma_pago' => 'cuotas',
            'cuotas' => [
                ['valor' => 6_000_000, 'vencimiento' => '2026-02-01'],
                ['valor' => 6_000_000, 'vencimiento' => '2026-03-01'],
            ],
        ]));

        $html = GeneratedDocument::with('currentVersion')->firstOrFail()->currentVersion->content_html;

        $this->assertStringContainsString('en 2 cuotas', $html);
        $this->assertStringContainsString('Cuota 1: $6.000.000', $html);
        $this->assertStringContainsString('Cuota 2: $6.000.000', $html);
    }

    /**
     * La propuesta es el antecedente contractual del contrato — las 5 cláusulas
     * legales (obligaciones, propiedad intelectual, confidencialidad, terminación)
     * deben ser LAS MISMAS filas de clause_blocks, no una redacción distinta.
     */
    public function test_shared_legal_clauses_render_identical_text_in_contract_and_proposal(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);

        $this->actingAs($user)->post(route('documents.contracts.generate'), [
            'client_id' => $client->id,
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
        $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id));

        $contract = GeneratedDocument::whereHas('documentType', fn ($q) => $q->where('key', 'contrato_servicios'))
            ->with('currentVersion')->firstOrFail();
        $proposal = GeneratedDocument::whereHas('documentType', fn ($q) => $q->where('key', 'propuesta_comercial'))
            ->with('currentVersion')->firstOrFail();

        foreach (['obligaciones_consultor', 'obligaciones_cliente', 'propiedad_intelectual', 'confidencialidad', 'terminacion_anticipada'] as $key) {
            $contractClause = collect($contract->currentVersion->clauses_data)->firstWhere('clause_block_key', $key);
            $proposalClause = collect($proposal->currentVersion->clauses_data)->firstWhere('clause_block_key', $key);

            $this->assertNotNull($contractClause, "Falta la cláusula {$key} en el contrato");
            $this->assertNotNull($proposalClause, "Falta la cláusula {$key} en la propuesta");
            $this->assertSame($contractClause['content_html'], $proposalClause['content_html'], "El texto de {$key} debería ser idéntico");
        }
    }

    public function test_index_lists_contracts_and_proposals_together(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);
        $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id));

        $response = $this->actingAs($user)->get(route('documents.contracts.index'));

        $response->assertOk();
        $response->assertSee('Propuesta de Servicios Profesionales');
    }

    public function test_show_page_renders_the_generated_document(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);
        $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->get(route('documents.proposals.show', $document));

        $response->assertOk();
        $response->assertSee($document->full_number);
        $response->assertSee('Cliente Demo S.A.S.');
    }

    public function test_pdf_downloads_successfully(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);
        $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->get(route('documents.proposals.pdf', $document));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertSame(1, $document->auditLogs()->where('event', 'downloaded_pdf')->count());
    }

    public function test_another_users_document_is_not_visible(): void
    {
        $owner = $this->makeUserWithTemplate();
        $intruder = User::factory()->create();
        $client = $this->createClient($owner);
        $this->actingAs($owner)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($intruder)->get(route('documents.proposals.show', $document));

        $response->assertForbidden();
    }

    public function test_sends_document_by_email_and_logs_audit_event(): void
    {
        Mail::fake();
        $user = $this->makeUserWithTemplate();
        $client = $this->createClient($user);
        $this->actingAs($user)->post(route('documents.proposals.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->post(route('documents.proposals.send_email', $document), [
            'email' => 'destinatario@example.com',
            'message' => 'Adjunto la propuesta para su revisión.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Mail::assertSent(ProposalDocumentMail::class, function (ProposalDocumentMail $mail) use ($document) {
            return $mail->hasTo('destinatario@example.com')
                && $mail->document->id === $document->id
                && $mail->customMessage === 'Adjunto la propuesta para su revisión.';
        });

        $log = $document->auditLogs()->where('event', 'emailed')->first();
        $this->assertNotNull($log);
        $this->assertSame('destinatario@example.com', $log->meta['to']);
    }
}
