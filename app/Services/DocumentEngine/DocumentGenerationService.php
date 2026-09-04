<?php

namespace App\Services\DocumentEngine;

use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Models\GeneratedDocument;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta la generación de un documento: congela los snapshots de
 * cliente y contratista, resuelve las cláusulas de la plantilla vía
 * ClauseEngine, asigna número atómico y crea el GeneratedDocument + su
 * primera DocumentVersion — todo en una transacción.
 */
final class DocumentGenerationService
{
    public function __construct(
        private readonly ClauseEngine $clauseEngine,
        private readonly DocumentNumberingService $numberingService,
    ) {
    }

    /**
     * @param array $variables respuestas del wizard paso 3 (número/año se asignan aparte —
     *   ciudad_celebracion, fecha_elaboracion, titulo_documento, servicios, duracion, honorarios)
     *
     * Bug real encontrado y corregido: `nextNumber()` asignaba el número
     * DENTRO de la misma transacción que crea el documento. Si el resto de
     * la transacción fallaba (ej. el `full_number` recién asignado ya
     * existe como fila de un documento eliminado: `generated_documents`
     * usa SoftDeletes, pero el índice UNIQUE de la base de datos no
     * distingue `deleted_at`, así que un número ya emitido nunca puede
     * reemitirse), Laravel deshacía también el incremento del contador —
     * dejándolo exactamente donde estaba, listo para volver a chocar con
     * el mismo número en el siguiente intento, indefinidamente (un simple
     * reintento con la misma lógica no alcanza a romper el ciclo). Ahora
     * `nextNumber()` se ejecuta y se confirma ANTES de abrir la transacción
     * del documento — igual que un consecutivo de factura, el número queda
     * consumido aunque el intento falle después, así que un reintento sí
     * avanza al siguiente número disponible en vez de repetir el mismo.
     */
    public function generate(User $user, Client $client, DocumentTemplate $template, array $variables): GeneratedDocument
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $numbering = $this->numberingService->nextNumber($user, $template->documentType);

            try {
                return $this->attemptGenerate($user, $client, $template, $variables, $numbering);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3 || ! str_contains($e->getMessage(), 'full_number')) {
                    throw $e;
                }
            }
        }
    }

    private function attemptGenerate(User $user, Client $client, DocumentTemplate $template, array $variables, array $numbering): GeneratedDocument
    {
        return DB::transaction(function () use ($user, $client, $template, $variables, $numbering) {
            $context = new PlaceholderContext($client, $user, $variables);
            $built = $this->clauseEngine->buildDocument($template, $context);

            $document = GeneratedDocument::create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'document_type_id' => $template->document_type_id,
                'template_id' => $template->id,
                'number' => $numbering['number'],
                'year' => $numbering['year'],
                'full_number' => $numbering['full_number'],
                'status' => 'borrador',
                'responsible_user_id' => $user->id,
                'client_snapshot' => $this->clientSnapshot($client),
                'contractor_snapshot' => $this->contractorSnapshot($user),
                'variables' => $variables,
            ]);

            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1,
                'clauses_data' => $built['clauses'],
                'content_html' => $built['content_html'],
                'change_summary' => 'Generación inicial',
                'created_by' => $user->id,
            ]);

            $document->update(['current_version_id' => $version->id]);

            return $document->fresh(['currentVersion', 'client', 'documentType']);
        });
    }

    private function clientSnapshot(Client $client): array
    {
        return [
            'name' => $client->name,
            'document_type' => $client->document_type,
            'document_number' => $client->document_number,
            'dv' => $client->dv,
            'full_document' => $client->getFullDocumentAttribute(),
            'person_type' => $client->person_type,
            'email' => $client->email,
            'phone' => $client->phone,
            'address' => $client->address,
            'city' => $client->city,
            'department' => $client->department,
            'contact_person' => $client->contact_person,
        ];
    }

    private function contractorSnapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'nit' => $user->nit,
            'identification_type' => $user->identification_type,
            'identification_number' => $user->identification_number,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'professional_card_number' => $user->professional_card_number,
            'logo_path' => $user->logo_path,
            'signature_path' => $user->signature_path,
        ];
    }
}
