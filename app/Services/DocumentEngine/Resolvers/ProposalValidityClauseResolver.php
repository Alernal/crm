<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use Carbon\Carbon;

/**
 * Cláusula de Validez y Aceptación: calcula la fecha límite de la
 * propuesta sumando días HÁBILES (salta sábado y domingo) a la fecha de
 * elaboración — sin calendario de festivos colombianos, simplificación
 * documentada.
 * $context->variables['validez'] = ['fecha_elaboracion_iso' => 'Y-m-d', 'dias' => int]
 */
final class ProposalValidityClauseResolver implements ClauseContextEnricher
{
    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $validez = $context->variables['validez'] ?? [];
        $fecha = ! empty($validez['fecha_elaboracion_iso']) ? Carbon::parse($validez['fecha_elaboracion_iso']) : Carbon::now();
        $dias = (int) ($validez['dias'] ?? 0);

        $limite = $fecha->copy();
        $restantes = $dias;
        while ($restantes > 0) {
            $limite->addDay();
            if (! $limite->isWeekend()) {
                $restantes--;
            }
        }

        return $context->withVariables([
            'fecha_vencimiento' => $limite->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
        ]);
    }
}
