<?php

namespace Tests\Feature\DocumentEngine;

use App\Mail\ContractDocumentMail;
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
 * Prueba de humo del flujo completo Fase 1: wizard -> generación ->
 * vista de solo lectura -> PDF, contra la plantilla real sembrada del
 * contrato de Consultoría Tributaria.
 */
class ContractGenerationFlowTest extends TestCase
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
            'bank_name' => 'Bancolombia',
            'account_type' => 'savings',
            'account_number' => '50615330253',
            'account_holder_name' => 'Andrés Felipe Arrieta Bertel',
            'account_holder_id' => '1102866622',
        ]);

        $this->seed(ContractTemplateSeeder::class);

        return $user;
    }

    private function wizardPayload(int $clientId, string $especialidad = 'tributaria'): array
    {
        return [
            'client_id' => $clientId,
            'especialidad' => $especialidad,
            'ciudad_celebracion' => 'Bogotá D.C.',
            'fecha_elaboracion' => '2026-01-15',
            'servicios' => [
                ['nombre' => 'Asesoría tributaria integral', 'descripcion' => null],
                ['nombre' => 'Declaraciones periódicas', 'descripcion' => 'IVA, Renta, ICA'],
            ],
            'duracion_modo' => 'meses',
            'duracion_meses' => 6,
            'fecha_inicio' => '2026-02-01',
            'valor' => 12_000_000,
            'periodicidad' => 'mensual',
            'valor_periodico' => 2_000_000,
        ];
    }

    public function test_wizard_page_loads_for_authenticated_user(): void
    {
        $user = $this->makeUserWithTemplate();

        $response = $this->actingAs($user)->get(route('documents.contracts.wizard'));

        $response->assertOk();
        $response->assertSee('Nuevo Contrato');
    }

    public function test_wizard_offers_the_three_specialties(): void
    {
        $user = $this->makeUserWithTemplate();

        $response = $this->actingAs($user)->get(route('documents.contracts.wizard'));

        $response->assertSee('Consultoría Tributaria', false);
        $response->assertSee('Consultoría Financiera', false);
        $response->assertSee('Consultoría Tributaria y Financiera', false);
    }

    public function test_store_requires_a_valid_especialidad(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);
        $payload = $this->wizardPayload($client->id);
        unset($payload['especialidad']);

        $response = $this->actingAs($user)->post(route('documents.contracts.generate'), $payload);

        $response->assertSessionHasErrors('especialidad');
    }

    public function test_title_is_always_generic_regardless_of_specialty(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);

        foreach (['tributaria', 'financiera', 'tributaria_financiera'] as $especialidad) {
            $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id, $especialidad));
        }

        $htmls = GeneratedDocument::with('currentVersion')->get()->map(fn ($d) => $d->currentVersion->content_html);

        foreach ($htmls as $html) {
            $this->assertStringContainsString('celebrar el presente CONTRATO DE PRESTACIÓN DE SERVICIOS conforme', $html);
            $this->assertStringNotContainsString('CONTRATO DE PRESTACIÓN DE SERVICIOS DE CONSULTORÍA', $html);
        }
    }

    public function test_financiera_specialty_uses_financial_wording_in_the_objeto_clause(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id, 'financiera'));

        $document = GeneratedDocument::with('currentVersion')->firstOrFail();
        $html = $document->currentVersion->content_html;

        $this->assertSame('financiera', $document->variables['especialidad']);
        // El título del documento no cambia, pero la Cláusula de Objeto sí debe hablar
        // de la especialidad correcta.
        $this->assertStringContainsString('prestación de servicios profesionales de consultoría financiera', $html);
        $this->assertStringContainsString('resultados económicos específicos derivados de sus recomendaciones financieras', $html);
        $this->assertStringNotContainsString('consultoría tributaria', $html);
        $this->assertStringNotContainsString('DIAN', $html);
    }

    public function test_tributaria_specialty_still_mentions_dian_in_the_disclaimer(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id, 'tributaria'));

        $html = GeneratedDocument::with('currentVersion')->firstOrFail()->currentVersion->content_html;

        $this->assertStringContainsString('prestación de servicios profesionales de consultoría tributaria', $html);
        $this->assertStringContainsString('garantiza resultados económicos o decisiones de la DIAN', $html);
    }

    public function test_combined_specialty_uses_combined_wording_in_the_objeto_clause(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id, 'tributaria_financiera'));

        $document = GeneratedDocument::with('currentVersion')->firstOrFail();

        $this->assertSame('tributaria_financiera', $document->variables['especialidad']);
        $this->assertStringContainsString('prestación de servicios profesionales de consultoría tributaria y financiera', $document->currentVersion->content_html);
    }

    public function test_index_always_shows_the_generic_document_type_label(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);
        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id, 'financiera'));

        $response = $this->actingAs($user)->get(route('documents.contracts.index'));

        $response->assertSee('Contrato de Prestación de Servicios');
        $response->assertDontSee('Consultoría Financiera');
    }

    public function test_pdf_has_no_footer_disclaimer(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);
        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->get(route('documents.contracts.print', $document));

        $response->assertDontSee('Generado electrónicamente por');
        $response->assertDontSee('versión '.$document->currentVersion->version_number, false);
    }

    public function test_generates_a_document_and_redirects_to_show(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'city' => 'Medellín',
            'address' => 'Carrera 4 # 5-6',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));

        $document = GeneratedDocument::first();
        $this->assertNotNull($document);
        $this->assertSame('001-'.now()->year, $document->full_number);
        $this->assertSame('borrador', $document->status);
        $this->assertSame($client->id, $document->client_id);
        $this->assertNotNull($document->current_version_id);

        $response->assertRedirect(route('documents.contracts.show', $document));
    }

    public function test_generated_document_resolves_all_clauses_without_leftover_placeholders(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'city' => 'Medellín',
            'address' => 'Carrera 4 # 5-6',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));

        $document = GeneratedDocument::with('currentVersion')->firstOrFail();
        $html = $document->currentVersion->content_html;

        $this->assertStringNotContainsString('{{', $html);
        $this->assertCount(19, $document->currentVersion->clauses_data);
        $this->assertStringContainsString('Asesoría tributaria integral', $html);
        $this->assertStringContainsString('DOCE MILLONES PESOS M/CTE', $html);
        $this->assertStringContainsString('SEIS (6) MESES', $html);
    }

    public function test_show_page_renders_the_generated_document(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);
        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->get(route('documents.contracts.show', $document));

        $response->assertOk();
        $response->assertSee($document->full_number);
        $response->assertSee('Cliente Demo S.A.S.');
    }

    public function test_pdf_downloads_successfully(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);
        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($user)->get(route('documents.contracts.pdf', $document));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertSame(1, $document->auditLogs()->where('event', 'downloaded_pdf')->count());
    }

    public function test_another_users_document_is_not_visible(): void
    {
        $owner = $this->makeUserWithTemplate();
        $intruder = User::factory()->create();
        $client = Client::create([
            'user_id' => $owner->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);
        $this->actingAs($owner)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));
        $document = GeneratedDocument::firstOrFail();

        $response = $this->actingAs($intruder)->get(route('documents.contracts.show', $document));

        $response->assertForbidden();
    }

    public function test_second_document_of_same_year_gets_next_consecutive_number(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));
        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));

        $numbers = GeneratedDocument::orderBy('id')->pluck('full_number')->all();
        $this->assertSame(['001-'.now()->year, '002-'.now()->year], $numbers);
    }

    public function test_legal_entity_client_contract_names_the_legal_representative_not_the_company(): void
    {
        $user = $this->makeUserWithTemplate();
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Franco Cerámicas S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '901822884',
            'dv' => '1',
            'person_type' => 'juridica',
            'address' => 'Calle el cauca',
            'chamber_of_commerce_city' => 'Sincelejo',
            'legal_representative_name' => 'María Gómez Restrepo',
            'legal_representative_document_type' => 'CC',
            'legal_representative_document_number' => '55667788',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));

        $document = GeneratedDocument::with('currentVersion')->firstOrFail();
        $html = $document->currentVersion->content_html;

        $this->assertStringContainsString('persona jurídica constituida conforme a las leyes de la República de Colombia', $html);
        $this->assertStringContainsString('representada en este acto por María Gómez Restrepo', $html);
        $this->assertStringContainsString('en su calidad de Representante Legal', $html);
        $this->assertStringContainsString('Cámara de Comercio de Sincelejo', $html);
        $this->assertStringNotContainsString('{{', $html);
    }

    private function generateDocument(User $user): GeneratedDocument
    {
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente Demo S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'email' => 'cliente@demo.com',
            'status' => 'active',
        ]);
        $this->actingAs($user)->post(route('documents.contracts.generate'), $this->wizardPayload($client->id));

        return GeneratedDocument::firstOrFail();
    }

    public function test_print_view_renders_and_logs_audit_event(): void
    {
        $user = $this->makeUserWithTemplate();
        $document = $this->generateDocument($user);

        $response = $this->actingAs($user)->get(route('documents.contracts.print', $document));

        $response->assertOk();
        $response->assertSee($document->full_number);
        $this->assertSame(1, $document->auditLogs()->where('event', 'printed')->count());
    }

    public function test_print_view_forbidden_for_another_user(): void
    {
        $owner = $this->makeUserWithTemplate();
        $intruder = User::factory()->create();
        $document = $this->generateDocument($owner);

        $response = $this->actingAs($intruder)->get(route('documents.contracts.print', $document));

        $response->assertForbidden();
    }

    public function test_sends_document_by_email_and_logs_audit_event(): void
    {
        Mail::fake();
        $user = $this->makeUserWithTemplate();
        $document = $this->generateDocument($user);

        $response = $this->actingAs($user)->post(route('documents.contracts.send_email', $document), [
            'email' => 'destinatario@example.com',
            'message' => 'Adjunto el contrato para su firma.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        Mail::assertSent(ContractDocumentMail::class, function (ContractDocumentMail $mail) use ($document) {
            return $mail->hasTo('destinatario@example.com')
                && $mail->document->id === $document->id
                && $mail->customMessage === 'Adjunto el contrato para su firma.';
        });

        $log = $document->auditLogs()->where('event', 'emailed')->first();
        $this->assertNotNull($log);
        $this->assertSame('destinatario@example.com', $log->meta['to']);
    }

    public function test_send_email_validates_required_email(): void
    {
        Mail::fake();
        $user = $this->makeUserWithTemplate();
        $document = $this->generateDocument($user);

        $response = $this->actingAs($user)->post(route('documents.contracts.send_email', $document), []);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    public function test_send_email_forbidden_for_another_user(): void
    {
        Mail::fake();
        $owner = $this->makeUserWithTemplate();
        $intruder = User::factory()->create();
        $document = $this->generateDocument($owner);

        $response = $this->actingAs($intruder)->post(route('documents.contracts.send_email', $document), [
            'email' => 'destinatario@example.com',
        ]);

        $response->assertForbidden();
        Mail::assertNothingSent();
    }
}
