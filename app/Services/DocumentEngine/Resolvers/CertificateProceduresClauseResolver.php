<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;

/**
 * Lista de procedimientos de verificación realizados por el contador
 * (RUT, soportes de transacciones, certificaciones laborales, etc.) —
 * editable en el wizard, precargada con los 5 ítems del modelo de
 * referencia. Mismo patrón que ServicesObjectClauseResolver.
 * $context->variables['procedimientos'] = string[]
 */
final class CertificateProceduresClauseResolver implements ClauseContextEnricher
{
    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $procedimientos = array_values(array_filter(
            $context->variables['procedimientos'] ?? [],
            fn ($p) => trim((string) $p) !== ''
        ));

        if ($procedimientos === []) {
            return $context->withVariables(['procedimientos_html' => '<p><em>[Sin procedimientos registrados]</em></p>']);
        }

        $items = array_map(fn ($p) => '<li>'.e(trim((string) $p)).'</li>', $procedimientos);

        return $context->withVariables(['procedimientos_html' => '<ol>'.implode('', $items).'</ol>']);
    }
}
