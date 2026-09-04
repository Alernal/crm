<?php

namespace App\Services\DocumentEngine;

/**
 * DTO desacoplado de Eloquent: representa una cláusula lista para
 * resolverse, ya sea que provenga de un TemplateClause "en construcción"
 * o de un snapshot congelado (document_template_versions.clauses_snapshot).
 */
final class ResolvableClause
{
    public function __construct(
        public readonly string $clauseBlockKey,
        public readonly string $title,
        public readonly string $rawContent,
        public readonly bool $isRequired,
        public readonly bool $isEditable,
        public readonly int $position,
    ) {
    }
}
