<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\NumberToWordsService;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;

/**
 * Cláusula de Pago: arma las variables de valor/periodicidad/forma de
 * pago desde el Paso 3 del wizard (sección Honorarios).
 * $context->variables['honorarios'] = ['valor' => float, 'periodicidad' => string,
 *   'valor_periodico' => float|null]
 */
final class PaymentClauseResolver implements ClauseContextEnricher
{
    private const PERIODICITY_LABELS = [
        'unico' => 'en un solo pago',
        'mensual' => 'de forma mensual',
        'bimestral' => 'de forma bimestral',
        'trimestral' => 'de forma trimestral',
        'semestral' => 'de forma semestral',
        'anual' => 'de forma anual',
    ];

    public function __construct(private readonly NumberToWordsService $numberToWords)
    {
    }

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $fees = $context->variables['honorarios'] ?? [];
        $valor = (float) ($fees['valor'] ?? 0);
        $valorPeriodico = (float) ($fees['valor_periodico'] ?? $valor);
        $periodicidad = $fees['periodicidad'] ?? 'unico';

        return $context->withVariables([
            'valor' => number_format($valor, 0, ',', '.'),
            'valor_letras' => $this->numberToWords->toPesos($valor),
            'valor_periodico' => number_format($valorPeriodico, 0, ',', '.'),
            'valor_periodico_letras' => $this->numberToWords->toPesos($valorPeriodico),
            'periodicidad_texto' => self::PERIODICITY_LABELS[$periodicidad] ?? $periodicidad,
        ]);
    }
}
