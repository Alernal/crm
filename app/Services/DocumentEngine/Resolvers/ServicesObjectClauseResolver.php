<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Models\GeneratedDocument;
use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;

/**
 * Constructor de servicios de la Cláusula de Objeto: numera
 * $context->variables['servicios'] (cada uno ['nombre' => ..., 'descripcion' => ...])
 * como lista jurídica ({{contrato.objeto}}). También inyecta el texto de la
 * especialidad elegida en el wizard (tributaria/financiera/ambas) — la frase
 * introductoria y el disclaimer de la cláusula cambian de tema con ella, no
 * solo el título del documento y la lista de servicios.
 */
final class ServicesObjectClauseResolver implements ClauseContextEnricher
{
    private const DEFAULT_ESPECIALIDAD = 'tributaria';

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $services = $context->variables['servicios'] ?? [];
        $especialidad = GeneratedDocument::especialidades()[$context->variables['especialidad'] ?? self::DEFAULT_ESPECIALIDAD]
            ?? GeneratedDocument::especialidades()[self::DEFAULT_ESPECIALIDAD];

        return $context->withVariables([
            'objeto' => $this->buildList($services),
            'especialidad_texto' => $especialidad['objeto_texto'],
            'especialidad_disclaimer' => $especialidad['disclaimer'],
        ]);
    }

    private function buildList(array $services): string
    {
        if ($services === []) {
            return '<p><em>[Sin servicios seleccionados]</em></p>';
        }

        $items = [];
        $last = array_key_last($services);

        foreach ($services as $index => $service) {
            $text = trim((string) ($service['nombre'] ?? ''));
            if (! empty($service['descripcion'])) {
                $text .= ': '.trim($service['descripcion']);
            }

            $items[] = '<li>'.e($text).($index === $last ? '.' : ';').'</li>';
        }

        return '<ol>'.implode('', $items).'</ol>';
    }
}
