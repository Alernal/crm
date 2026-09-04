<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\Providers\ClientPlaceholderProvider;
use App\Services\DocumentEngine\ResolvableClause;

/**
 * Arma el bloque de firma del contador con fallback "—" para identificación/T.P.
 * — mismo motivo que ProposalPartyDetailsClauseResolver: el bloque de firma es
 * HTML con placeholders literales, así que un campo sin diligenciar dejaría
 * {{empresa.tarjeta_profesional}} visible en el PDF en vez de "—". Cada dato va
 * en su propia línea (nunca combinados en una sola, ej. "C.C. · T.P.").
 *
 * Email/teléfono/dirección del contador NO viven aquí — a pedido explícito del
 * usuario se movieron a un pie de página corporativo propio (ver `.footer` en
 * `documents/certificates/pdf.blade.php`/`show.blade.php`), separado de la
 * firma: la firma certifica QUIÉN firma (identidad legal — cédula, tarjeta
 * profesional), el pie de página es información de contacto de la firma, un
 * rol distinto que no debe mezclarse con la identidad de quien certifica.
 */
final class CertificateSignatureClauseResolver implements ClauseContextEnricher
{
    private const BLANK = '—';

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $company = $context->company;

        // Identificación PERSONAL de EL CONTADOR: siempre cédula/CE/pasaporte, nunca NIT
        // (un NIT es un registro tributario, no un documento de identidad) — mismo criterio
        // que CompanyPlaceholderProvider::resolve('tipo_identificacion'/'identificacion').
        // En la firma se usa la abreviatura ("C.C."), no el texto completo.
        $idType = ClientPlaceholderProvider::documentTypeAbbreviation($company->identification_type ?: 'CC');
        $idNumber = $company->identification_number ?: $company->nit;

        $lines = [
            $idType.' No. '.e($idNumber ?: self::BLANK),
            'T.P. No. '.e($company->professional_card_number ?: self::BLANK),
        ];

        return $context->withVariables(['firma_detalle_html' => implode('', array_map(
            fn ($line) => '<div class="signature-detail">'.$line.'</div>',
            $lines
        ))]);
    }
}
