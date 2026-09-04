<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\NumberToWordsService;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use Carbon\Carbon;

/**
 * Cláusula de Duración: el usuario define fecha_inicio + fecha_fin, o
 * fecha_inicio + número de meses (el motor calcula la fecha faltante).
 * $context->variables['duracion'] = ['modo' => 'meses'|'fechas', 'meses' => int|null,
 *   'fecha_inicio' => 'Y-m-d'|null, 'fecha_fin' => 'Y-m-d'|null]
 *
 * duracion_texto queda disponible para OTRAS cláusulas (ej. Pago, que la
 * cita textualmente) porque el enrichment corre en una pasada compartida.
 */
final class DurationClauseResolver implements ClauseContextEnricher
{
    public function __construct(private readonly NumberToWordsService $numberToWords)
    {
    }

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $duration = $context->variables['duracion'] ?? [];
        $modo = $duration['modo'] ?? 'meses';
        $fechaInicio = ! empty($duration['fecha_inicio']) ? Carbon::parse($duration['fecha_inicio']) : null;

        if ($modo === 'meses') {
            $meses = (int) ($duration['meses'] ?? 0);
            $fechaFin = $fechaInicio?->copy()->addMonths($meses);
        } else {
            $fechaFin = ! empty($duration['fecha_fin']) ? Carbon::parse($duration['fecha_fin']) : null;
            $meses = ($fechaInicio && $fechaFin) ? $fechaInicio->diffInMonths($fechaFin) : 0;
        }

        return $context->withVariables([
            'duracion_texto' => sprintf('%s (%d) MESES', mb_strtoupper($this->numberToWords->convert($meses)), $meses),
            'fecha_inicio' => $fechaInicio?->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'fecha_fin' => $fechaFin?->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
        ]);
    }
}
