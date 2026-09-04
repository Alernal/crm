<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Models\Client;
use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\Providers\ClientPlaceholderProvider;
use App\Services\DocumentEngine\ResolvableClause;

/**
 * Preámbulo del contrato: cuando EL CLIENTE es persona jurídica, quien
 * firma es el representante legal, no la empresa — la redacción cambia
 * de estructura (no es solo sustituir placeholders, es una frase
 * distinta). Arma la descripción completa de EL CLIENTE como una sola
 * variable ({{contrato.descripcion_cliente}}) y deja la parte de EL
 * CONSULTOR + cierre en el texto estático de la plantilla, que es igual
 * en ambos casos.
 */
final class PreambleClauseResolver implements ClauseContextEnricher
{
    private const BLANK = '__________';

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        $client = $context->client;

        $description = $client === null
            ? '[CLIENTE NO SELECCIONADO]'
            : ($client->person_type === 'juridica'
                ? $this->legalEntityDescription($client)
                : $this->naturalPersonDescription($client));

        return $context->withVariables(['descripcion_cliente' => $description]);
    }

    private function naturalPersonDescription(Client $client): string
    {
        return sprintf(
            '%s, mayor de edad, identificado(a) con %s No. %s, expedida en %s, con domicilio en %s',
            e($client->name),
            ClientPlaceholderProvider::documentTypeLabel($client->document_type),
            e($client->getFullDocumentAttribute()),
            e($client->city ?: self::BLANK),
            e($client->address ?: self::BLANK)
        );
    }

    private function legalEntityDescription(Client $client): string
    {
        return sprintf(
            '%s, persona jurídica constituida conforme a las leyes de la República de Colombia, identificada con NIT No. %s, '
            .'con domicilio en %s, inscrita en el Registro Mercantil ante la Cámara de Comercio de %s, '
            .'representada en este acto por %s, mayor de edad, identificado con %s No. %s, '
            .'en su calidad de Representante Legal debidamente constituido y autorizado',
            e($client->name),
            e($client->getFullDocumentAttribute()),
            e($client->address ?: self::BLANK),
            e($client->chamber_of_commerce_city ?: $client->city ?: self::BLANK),
            e($client->legal_representative_name ?: '['.self::BLANK.' — falta registrar el representante legal en la ficha del cliente]'),
            ClientPlaceholderProvider::documentTypeLabel($client->legal_representative_document_type ?: 'CC'),
            e($client->legal_representative_document_number ?: self::BLANK)
        );
    }
}
