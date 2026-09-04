<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\NumberToWordsService;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use Carbon\Carbon;

/**
 * Único bloque computed del Certificado de Ingresos: calcula el período
 * certificado en texto, el valor del ingreso en cifras/letras (mismo
 * NumberToWordsService de Contratos/Propuestas) y la frase opcional de
 * Grupo NIIF — todas quedan disponibles para el resto de cláusulas
 * (identificación, marco normativo, procedimientos) vía el contexto
 * compartido de ClauseEngine, aunque este resolver está adjunto solo a
 * 'certificado_certificacion'.
 * $context->variables['periodo'] = ['fecha_inicio' => 'Y-m-d', 'fecha_fin' => 'Y-m-d']
 * $context->variables['ingreso'] = ['valor' => float, 'periodicidad' => 'anual'|'mensual'|'otro']
 * $context->variables['grupo_niif'] = 'no_aplica'|'1'|'2'|'3'
 */
final class CertificateIncomeClauseResolver implements ClauseContextEnricher
{
    private const PERIODICITY_LABELS = [
        'anual' => 'anual',
        'mensual' => 'mensual',
        'otro' => 'correspondiente al período certificado',
    ];

    public function __construct(private readonly NumberToWordsService $numberToWords)
    {
    }

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $periodo = $context->variables['periodo'] ?? [];
        $inicio = ! empty($periodo['fecha_inicio']) ? Carbon::parse($periodo['fecha_inicio']) : null;
        $fin = ! empty($periodo['fecha_fin']) ? Carbon::parse($periodo['fecha_fin']) : null;

        $periodoTexto = ($inicio && $fin)
            ? sprintf(
                'del %s al %s',
                $inicio->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
                $fin->locale('es')->isoFormat('D [de] MMMM [de] YYYY')
            )
            : '[período no definido]';

        $ingreso = $context->variables['ingreso'] ?? [];
        $valor = (float) ($ingreso['valor'] ?? 0);
        $periodicidad = $ingreso['periodicidad'] ?? 'anual';

        $grupoNiif = $context->variables['grupo_niif'] ?? 'no_aplica';
        $grupoNiifTexto = $grupoNiif === 'no_aplica'
            ? ''
            : ' El(la) señor(a) '.e((string) ($context->client?->name ?? '')).' pertenece al Grupo '.e($grupoNiif).' de NIIF.';

        return $context->withVariables([
            'periodo_texto' => $periodoTexto,
            'ingreso_valor_letras' => $this->numberToWords->toPesos($valor),
            'ingreso_valor_formateado' => number_format($valor, 0, ',', '.'),
            'ingreso_periodicidad_texto' => self::PERIODICITY_LABELS[$periodicidad] ?? $periodicidad,
            'grupo_niif_texto' => $grupoNiifTexto,
        ]);
    }
}
