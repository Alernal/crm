<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\NumberToWordsService;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\ResolvableClause;
use Carbon\Carbon;

/**
 * Cláusula de Inversión y Forma de Pago: arma el valor en letras (mismo
 * NumberToWordsService del contrato) y el desglose de pago único o en
 * cuotas como una sola variable HTML ya escapada.
 * $context->variables['inversion'] = ['valor' => float, 'forma_pago' => 'unico'|'cuotas',
 *   'cuotas' => [['valor' => float, 'vencimiento' => 'Y-m-d'], ...], 'condiciones_pago' => ?string]
 */
final class ProposalPaymentClauseResolver implements ClauseContextEnricher
{
    public function __construct(private readonly NumberToWordsService $numberToWords)
    {
    }

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $inversion = $context->variables['inversion'] ?? [];
        $valor = (float) ($inversion['valor'] ?? 0);
        $formaPago = $inversion['forma_pago'] ?? 'unico';
        $cuotas = $inversion['cuotas'] ?? [];
        $condiciones = trim((string) ($inversion['condiciones_pago'] ?? ''));

        $html = '<p><strong>Valor total de los servicios:</strong> '
            .e($this->numberToWords->toPesos($valor)).' ($'.number_format($valor, 0, ',', '.').')</p>';

        if ($formaPago === 'cuotas' && $cuotas !== []) {
            $items = [];
            foreach ($cuotas as $i => $cuota) {
                $items[] = sprintf(
                    '<li>Cuota %d: $%s (vencimiento: %s)</li>',
                    $i + 1,
                    number_format((float) ($cuota['valor'] ?? 0), 0, ',', '.'),
                    e(Carbon::parse($cuota['vencimiento'])->locale('es')->isoFormat('D [de] MMMM [de] YYYY'))
                );
            }
            $html .= '<p><strong>Forma de pago:</strong> en '.count($cuotas).' cuotas, así:</p><ul>'.implode('', $items).'</ul>';
        } else {
            $html .= '<p><strong>Forma de pago:</strong> pago único al inicio de los servicios.</p>';
        }

        if ($condiciones !== '') {
            $html .= '<p><strong>Condiciones de pago:</strong> '.nl2br(e($condiciones)).'</p>';
        }

        return $context->withVariables(['inversion_html' => $html]);
    }
}
