<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;

/**
 * Cláusula de Objetivos de la propuesta: arma el objetivo general (párrafo)
 * y la lista numerada de objetivos específicos (1 a 4, del wizard) como una
 * sola variable HTML ya escapada ({{propuesta.objetivos_html}}).
 * $context->variables['objetivos'] = ['general' => string, 'especificos' => string[]]
 */
final class ProposalObjectivesClauseResolver implements ClauseContextEnricher
{
    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $objetivos = $context->variables['objetivos'] ?? [];
        $general = trim((string) ($objetivos['general'] ?? ''));
        $especificos = array_values(array_filter(
            $objetivos['especificos'] ?? [],
            fn ($o) => trim((string) $o) !== ''
        ));

        $html = '<p><strong>Objetivo General:</strong></p><p>'
            .nl2br(e($general !== '' ? $general : '[Sin objetivo general definido]'))
            .'</p>';

        if ($especificos !== []) {
            $items = array_map(fn ($o) => '<li>'.e(trim((string) $o)).'</li>', $especificos);
            $html .= '<p><strong>Objetivos Específicos:</strong></p><ol>'.implode('', $items).'</ol>';
        }

        return $context->withVariables(['objetivos_html' => $html]);
    }
}
