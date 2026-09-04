<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\Client;
use App\Models\ClauseBlock;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\TemplateClause;
use App\Models\User;
use App\Services\DocumentEngine\ClauseEngine;
use App\Services\DocumentEngine\PlaceholderContext;
use Database\Seeders\ClauseBlockSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba de integración del Constructor Inteligente de Cláusulas contra
 * el catálogo REAL sembrado desde el contrato analizado
 * (Documentos/CONTRATO_CONSULTORIA_TRIBUTARIA.docx) — no contra datos de
 * juguete. Verifica el bug real encontrado durante el desarrollo: una
 * variable calculada por un enricher (duracion_texto) debe quedar
 * disponible para OTRA cláusula (Pago) que la cita textualmente.
 */
class ClauseEngineTest extends TestCase
{
    use RefreshDatabase;

    private function makeFullContractTemplate(User $user, DocumentType $type): DocumentTemplate
    {
        $template = DocumentTemplate::create([
            'user_id' => $user->id,
            'document_type_id' => $type->id,
            'name' => 'Contrato Consultoría Tributaria',
            'status' => 'active',
        ]);

        $order = [
            'preambulo', 'objeto_servicios', 'duracion', 'obligaciones_consultor',
            'obligaciones_cliente', 'clausula_pago', 'fuerza_mayor', 'mora_suspension',
            'clausula_penal', 'terminacion_anticipada', 'retencion_documentos',
            'solucion_controversias', 'naturaleza_juridica', 'propiedad_intelectual',
            'confidencialidad', 'exclusiones_alcance', 'conservacion_documentacion',
            'disposiciones_finales', 'firmas',
        ];

        foreach ($order as $position => $key) {
            TemplateClause::create([
                'template_id' => $template->id,
                'clause_block_id' => ClauseBlock::where('key', $key)->value('id'),
                'position' => $position,
                'is_required' => true,
                'is_editable' => true,
                'is_active' => true,
            ]);
        }

        return $template->fresh();
    }

    private function fullContext(?Client $client, User $user): PlaceholderContext
    {
        return new PlaceholderContext($client, $user, [
            'titulo_documento' => 'CONTRATO DE PRESTACIÓN DE SERVICIOS DE CONSULTORÍA TRIBUTARIA',
            'ciudad_celebracion' => 'Bogotá D.C.',
            'fecha_elaboracion' => '15 días del mes de enero de 2026',
            'servicios' => [
                ['nombre' => 'Asesoría tributaria integral'],
                ['nombre' => 'Declaraciones periódicas', 'descripcion' => 'IVA, Renta, ICA'],
            ],
            'duracion' => ['modo' => 'meses', 'meses' => 6, 'fecha_inicio' => '2026-02-01'],
            'honorarios' => ['valor' => 12_000_000, 'periodicidad' => 'mensual', 'valor_periodico' => 2_000_000],
        ]);
    }

    public function test_builds_all_19_clauses_of_the_real_contract_without_leftover_placeholders(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(ClauseBlockSeeder::class);

        $user = User::factory()->create([
            'nit' => '900123456-1',
            'city' => 'Bogotá D.C.',
            'address' => 'Calle 1 # 2-3',
            'bank_name' => 'Bancolombia',
            'account_type' => 'savings',
            'account_number' => '50615330253',
            'account_holder_name' => 'Andrés Felipe Arrieta Bertel',
            'account_holder_id' => '1102866622',
        ]);
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Cliente de Prueba S.A.S.',
            'document_type' => 'NIT',
            'document_number' => '900987654',
            'city' => 'Medellín',
            'address' => 'Carrera 4 # 5-6',
        ]);
        $type = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $template = $this->makeFullContractTemplate($user, $type);

        $result = app(ClauseEngine::class)->buildDocument($template, $this->fullContext($client, $user));

        $this->assertCount(19, $result['clauses']);
        $this->assertStringNotContainsString('{{', $result['content_html']);
        $this->assertStringNotContainsString('}}', $result['content_html']);
    }

    public function test_variable_computed_for_one_clause_is_available_to_another(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(ClauseBlockSeeder::class);

        $user = User::factory()->create();
        $type = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $template = $this->makeFullContractTemplate($user, $type);

        $result = app(ClauseEngine::class)->buildDocument($template, $this->fullContext(null, $user));

        // "SEIS (6) MESES" lo calcula el enricher de la Cláusula de Duración,
        // pero debe aparecer también en el texto de la Cláusula de Pago.
        $paymentClause = collect($result['clauses'])->firstWhere('clause_block_key', 'clausula_pago');
        $this->assertStringContainsString('SEIS (6) MESES', $paymentClause['content_html']);
    }

    public function test_marks_dynamic_clauses_correctly(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(ClauseBlockSeeder::class);

        $user = User::factory()->create();
        $type = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $template = $this->makeFullContractTemplate($user, $type);

        $result = app(ClauseEngine::class)->buildDocument($template, $this->fullContext(null, $user));
        $clausesByKey = collect($result['clauses'])->keyBy('clause_block_key');

        $this->assertTrue($clausesByKey['objeto_servicios']['is_dynamic']);
        $this->assertTrue($clausesByKey['duracion']['is_dynamic']);
        $this->assertTrue($clausesByKey['clausula_pago']['is_dynamic']);
        $this->assertFalse($clausesByKey['confidencialidad']['is_dynamic']);
    }

    public function test_inactive_clause_is_excluded_from_the_document(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $this->seed(ClauseBlockSeeder::class);

        $user = User::factory()->create();
        $type = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $template = $this->makeFullContractTemplate($user, $type);

        $template->clauses()->where('clause_block_id', ClauseBlock::where('key', 'exclusiones_alcance')->value('id'))
            ->update(['is_active' => false]);

        $result = app(ClauseEngine::class)->buildDocument($template->fresh(), $this->fullContext(null, $user));

        $this->assertCount(18, $result['clauses']);
        $this->assertNull(collect($result['clauses'])->firstWhere('clause_block_key', 'exclusiones_alcance'));
    }
}
