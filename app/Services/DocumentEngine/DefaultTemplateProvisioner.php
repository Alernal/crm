<?php

namespace App\Services\DocumentEngine;

use App\Models\ClauseBlock;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Aprovisiona las plantillas oficiales del sistema para un usuario — al
 * sembrar la base de datos para usuarios ya existentes, y al registrarse
 * un usuario nuevo (ver RegisteredUserController), para que el Motor
 * Documental tenga al menos una plantilla lista para usar desde el
 * primer momento en cada tipo de documento soportado.
 */
final class DefaultTemplateProvisioner
{
    /** Orden exacto de las 19 cláusulas del contrato real (preámbulo → firmas). */
    private const CONTRACT_CLAUSE_ORDER = [
        'preambulo', 'objeto_servicios', 'duracion', 'obligaciones_consultor',
        'obligaciones_cliente', 'clausula_pago', 'fuerza_mayor', 'mora_suspension',
        'clausula_penal', 'terminacion_anticipada', 'retencion_documentos',
        'solucion_controversias', 'naturaleza_juridica', 'propiedad_intelectual',
        'confidencialidad', 'exclusiones_alcance', 'conservacion_documentacion',
        'disposiciones_finales', 'firmas',
    ];

    /**
     * Orden de la plantilla de Propuesta Comercial (modelo
     * Documentos/PROPUESTA_EJEMPLO_COMPLETO.docx). Las 5 claves de
     * "Términos y Condiciones" (obligaciones_consultor..terminacion_anticipada)
     * son las MISMAS filas de clause_blocks que usa el contrato — no se
     * duplica el texto legal, solo se le da un título propio vía
     * $legalSectionTitleOverrides (la propuesta es el antecedente
     * contractual del contrato, deben compartir el mismo texto).
     */
    private const PROPOSAL_CLAUSE_ORDER = [
        'propuesta_datos_generales', 'propuesta_datos_cliente', 'propuesta_datos_consultor',
        'propuesta_descripcion_proyecto', 'propuesta_objetivos', 'propuesta_alcance_servicios',
        'propuesta_servicios_no_incluidos', 'propuesta_metodologia', 'propuesta_inversion_pago',
        'obligaciones_consultor', 'obligaciones_cliente', 'propiedad_intelectual',
        'confidencialidad', 'terminacion_anticipada',
        'propuesta_validez_aceptacion', 'propuesta_firmas',
    ];

    /** title_override para las cláusulas legales reutilizadas del contrato dentro de la propuesta. */
    private const PROPOSAL_LEGAL_TITLE_OVERRIDES = [
        'obligaciones_consultor' => '10.1 RESPONSABILIDADES DEL CONSULTOR',
        'obligaciones_cliente' => '10.2 RESPONSABILIDADES DEL CLIENTE',
        'propiedad_intelectual' => '10.3 PROPIEDAD INTELECTUAL',
        'confidencialidad' => '10.4 CONFIDENCIALIDAD',
        'terminacion_anticipada' => '10.5 TERMINACIÓN',
    ];

    /** Orden de la plantilla de Certificado de Ingresos (modelo VB25-Certificado-contador-ingresos-persona-natural.docx). */
    private const CERTIFICATE_CLAUSE_ORDER = [
        'certificado_encabezado', 'certificado_identificacion', 'certificado_marco_normativo',
        'certificado_procedimientos', 'certificado_resultado', 'certificado_certificacion',
        'certificado_firma',
    ];

    public function provisionFor(User $user): void
    {
        $this->provisionContractFor($user);
        $this->provisionProposalFor($user);
        $this->provisionCertificateFor($user);
    }

    private function provisionContractFor(User $user): void
    {
        $type = DocumentType::where('key', 'contrato_servicios')->first();
        if (! $type) {
            return; // catálogo aún no sembrado (ej. entornos de test que no corren DocumentTypeSeeder)
        }

        if ($this->alreadyProvisioned($user, $type)) {
            return;
        }

        DB::transaction(function () use ($user, $type) {
            $template = DocumentTemplate::create([
                'user_id' => $user->id,
                'document_type_id' => $type->id,
                'name' => 'Contrato de Prestación de Servicios de Consultoría Tributaria',
                'description' => 'Plantilla oficial del sistema, lista para usar. Puedes editarla o duplicarla desde Documentos › Plantillas.',
                'is_system_default' => true,
                'status' => DocumentTemplate::STATUS_ACTIVE,
            ]);

            $blockIds = ClauseBlock::whereIn('key', self::CONTRACT_CLAUSE_ORDER)->pluck('id', 'key');

            foreach (self::CONTRACT_CLAUSE_ORDER as $position => $key) {
                $template->clauses()->create([
                    'clause_block_id' => $blockIds[$key],
                    'position' => $position,
                    'is_required' => true,
                    'is_editable' => true,
                    'is_active' => true,
                ]);
            }
        });
    }

    private function provisionProposalFor(User $user): void
    {
        $type = DocumentType::where('key', 'propuesta_comercial')->first();
        if (! $type) {
            return;
        }

        if ($this->alreadyProvisioned($user, $type)) {
            return;
        }

        DB::transaction(function () use ($user, $type) {
            $template = DocumentTemplate::create([
                'user_id' => $user->id,
                'document_type_id' => $type->id,
                'name' => 'Propuesta de Servicios Profesionales de Consultoría Tributaria y Financiera',
                'description' => 'Plantilla oficial del sistema, lista para usar. Puedes editarla o duplicarla desde Documentos › Plantillas.',
                'is_system_default' => true,
                'status' => DocumentTemplate::STATUS_ACTIVE,
            ]);

            $blockIds = ClauseBlock::whereIn('key', self::PROPOSAL_CLAUSE_ORDER)->pluck('id', 'key');

            foreach (self::PROPOSAL_CLAUSE_ORDER as $position => $key) {
                $template->clauses()->create([
                    'clause_block_id' => $blockIds[$key],
                    'position' => $position,
                    'title_override' => self::PROPOSAL_LEGAL_TITLE_OVERRIDES[$key] ?? null,
                    'is_required' => true,
                    'is_editable' => true,
                    'is_active' => true,
                ]);
            }
        });
    }

    private function provisionCertificateFor(User $user): void
    {
        $type = DocumentType::where('key', 'certificado_ingresos')->first();
        if (! $type) {
            return;
        }

        if ($this->alreadyProvisioned($user, $type)) {
            return;
        }

        DB::transaction(function () use ($user, $type) {
            $template = DocumentTemplate::create([
                'user_id' => $user->id,
                'document_type_id' => $type->id,
                'name' => 'Certificado de Ingresos de Persona Natural',
                'description' => 'Plantilla oficial del sistema, lista para usar. Puedes editarla o duplicarla desde Documentos › Plantillas.',
                'is_system_default' => true,
                'status' => DocumentTemplate::STATUS_ACTIVE,
            ]);

            $blockIds = ClauseBlock::whereIn('key', self::CERTIFICATE_CLAUSE_ORDER)->pluck('id', 'key');

            foreach (self::CERTIFICATE_CLAUSE_ORDER as $position => $key) {
                $template->clauses()->create([
                    'clause_block_id' => $blockIds[$key],
                    'position' => $position,
                    'is_required' => true,
                    'is_editable' => true,
                    'is_active' => true,
                ]);
            }
        });
    }

    private function alreadyProvisioned(User $user, DocumentType $type): bool
    {
        return DocumentTemplate::where('user_id', $user->id)
            ->where('document_type_id', $type->id)
            ->where('is_system_default', true)
            ->exists();
    }
}
