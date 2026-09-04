<?php

namespace App\Services\DocumentEngine\Resolvers;

use App\Services\DocumentEngine\ClauseContextEnricher;
use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\Providers\ClientPlaceholderProvider;
use App\Services\DocumentEngine\ResolvableClause;

/**
 * Arma los bloques "Datos del Cliente" / "Datos del Consultor" de la
 * propuesta con fallback "—" para campos opcionales vacíos (dirección,
 * ciudad, contacto, teléfono) — a diferencia del passthrough genérico de
 * ProposalPlaceholderProvider, que dejaría el placeholder literal
 * ({{cliente.direccion}}) visible en el documento si el dato no está
 * diligenciado. Un solo resolver atiende ambos clause_blocks, distinguidos
 * por $clause->clauseBlockKey.
 */
final class ProposalPartyDetailsClauseResolver implements ClauseContextEnricher
{
    private const BLANK = '—';

    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext
    {
        return match ($clause->clauseBlockKey) {
            'propuesta_datos_cliente' => $context->withVariables(['datos_cliente_html' => $this->clientDetails($context)]),
            'propuesta_datos_consultor' => $context->withVariables(['datos_consultor_html' => $this->companyDetails($context)]),
            default => $context,
        };
    }

    private function clientDetails(PlaceholderContext $context): string
    {
        $client = $context->client;
        if ($client === null) {
            return '<p>[Cliente no seleccionado]</p>';
        }

        $lines = [
            'Razón social / Nombre: '.e($client->name),
            'Tipo de identificación: '.e(ClientPlaceholderProvider::documentTypeLabel($client->document_type)).' — Número: '.e($client->getFullDocumentAttribute()),
            'Domicilio: '.e($client->address ?: self::BLANK).' — '.e($client->city ?: self::BLANK),
            'Contacto: '.e($client->contact_person ?: self::BLANK),
            'Teléfono: '.e($client->phone ?: self::BLANK),
            'Email: '.e($client->email ?: self::BLANK),
        ];

        return '<p>'.implode('<br>', $lines).'</p>';
    }

    private function companyDetails(PlaceholderContext $context): string
    {
        $company = $context->company;

        // Identificación PERSONAL de EL CONSULTOR: siempre cédula/CE/pasaporte, nunca NIT
        // (mismo criterio que CompanyPlaceholderProvider) — el número dedicado tiene
        // prioridad; si aún no se ha diligenciado, se reutiliza el NIT como valor (en
        // Colombia son los mismos dígitos para persona natural) pero jamás como etiqueta.
        $cedulaNumero = $company->identification_number ?: $company->nit;
        $cedula = $cedulaNumero
            ? ClientPlaceholderProvider::documentTypeAbbreviation($company->identification_type ?: 'CC').' No. '.$cedulaNumero
            : self::BLANK;

        $lines = [
            'Nombre completo: '.e($company->name),
            'Cédula: '.e($cedula),
            'NIT: '.e($company->nit ?: self::BLANK),
            'Tarjeta Profesional: '.e($company->professional_card_number ?: self::BLANK),
            'Domicilio: '.e($company->address ?: self::BLANK).' — '.e($company->city ?: self::BLANK),
            'Teléfono: '.e($company->phone ?: self::BLANK),
            'Email: '.e($company->email),
        ];

        return '<p>'.implode('<br>', $lines).'</p>';
    }
}
