<?php

namespace App\Services\DocumentEngine;

use App\Models\ClauseBlock;
use App\Models\DocumentTemplate;
use App\Models\TemplateClause;

/**
 * Constructor Inteligente de Cláusulas. Corre en dos pasadas sobre un
 * PlaceholderContext COMPARTIDO:
 *
 *   1) Enriquecimiento: cada cláusula cuyo clause_block declara un
 *      resolver_class (computed/builder) calcula sus variables derivadas
 *      (fechas, montos en letras, listas) y las acumula en el contexto —
 *      así una variable calculada por la Cláusula de Duración también
 *      queda disponible para la Cláusula de Pago, sin acoplar una a otra.
 *   2) Render: con el contexto ya completo, se sustituyen los
 *      placeholders de TODAS las cláusulas (estáticas y dinámicas) vía
 *      PlaceholderEngine.
 */
final class ClauseEngine
{
    public function __construct(private readonly PlaceholderEngine $placeholderEngine)
    {
    }

    public function buildDocument(DocumentTemplate $template, PlaceholderContext $context): array
    {
        $clauses = $template->clauses()->with('clauseBlock')->where('is_active', true)->orderBy('position')->get();

        // Pasada 1: enriquecimiento compartido.
        foreach ($clauses as $templateClause) {
            $enricher = $this->enricherFor($templateClause->clauseBlock);
            if ($enricher !== null) {
                $context = $enricher->enrich($this->toResolvable($templateClause), $context);
            }
        }

        // Pasada 2: render final con el contexto ya completo.
        $clausesData = [];
        $htmlParts = [];

        foreach ($clauses as $templateClause) {
            $resolved = $this->placeholderEngine->render($templateClause->rawContent(), $context);

            $clausesData[] = [
                'clause_block_id' => $templateClause->clause_block_id,
                'clause_block_key' => $templateClause->clauseBlock->key,
                'title' => $templateClause->title(),
                'content_html' => $resolved,
                'position' => $templateClause->position,
                'is_required' => $templateClause->is_required,
                'is_editable' => $templateClause->is_editable,
                'is_dynamic' => $templateClause->clauseBlock->resolver_strategy !== ClauseBlock::STRATEGY_STATIC,
            ];

            $htmlParts[] = $this->wrapClauseHtml($templateClause->title(), $resolved);
        }

        return [
            'clauses' => $clausesData,
            'content_html' => implode("\n", $htmlParts),
        ];
    }

    private function enricherFor(ClauseBlock $clauseBlock): ?ClauseContextEnricher
    {
        if (! $clauseBlock->resolver_class) {
            return null;
        }

        $instance = app($clauseBlock->resolver_class);

        return $instance instanceof ClauseContextEnricher ? $instance : null;
    }

    private function toResolvable(TemplateClause $templateClause): ResolvableClause
    {
        return new ResolvableClause(
            clauseBlockKey: $templateClause->clauseBlock->key,
            title: $templateClause->title(),
            rawContent: $templateClause->rawContent(),
            isRequired: $templateClause->is_required,
            isEditable: $templateClause->is_editable,
            position: $templateClause->position,
        );
    }

    /**
     * Marcadores seguros que un `default_content` puede usar para pedir énfasis
     * (negrita) o salto de línea SIN dejar de pasar por el escape de texto plano
     * más abajo — [[B]]/[[/B]]/[[BR]] no contienen '<' ni '>' ni '&', así que
     * `e()` los deja intactos mientras sí neutraliza cualquier '<'/'>'/'&' real
     * que venga de un dato de cliente (nombre, dirección...) sustituido en la
     * misma cláusula. Se reemplazan por las etiquetas reales DESPUÉS del escape,
     * nunca antes — de lo contrario el dato del cliente viajaría sin escapar.
     */
    private const SAFE_MARKUP_TOKENS = [
        '[[B]]' => '<strong>',
        '[[/B]]' => '</strong>',
        '[[BR]]' => '<br>',
    ];

    private function wrapClauseHtml(string $title, string $body): string
    {
        $titleHtml = $title !== '' ? '<p><strong>'.e($title).'.</strong></p>' : '';

        // Cláusulas puramente estáticas (texto plano, incluidos los marcadores
        // [[B]]/[[BR]] de arriba) se escapan y envuelven en <p>. Cuerpos que ya
        // traen HTML de confianza (inyectado por un enricher, ej. <ol>) se
        // insertan tal cual para no escapar por error las etiquetas.
        $bodyHtml = strip_tags($body) === $body
            ? strtr('<p>'.nl2br(e($body)).'</p>', self::SAFE_MARKUP_TOKENS)
            : $body;

        return '<div class="clause">'.$titleHtml.$bodyHtml.'</div>';
    }
}
