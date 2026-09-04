<?php

namespace App\Services\DocumentEngine\Providers;

use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\PlaceholderProvider;

final class ClientPlaceholderProvider implements PlaceholderProvider
{
    public const DOCUMENT_TYPE_LABELS = [
        'NIT' => 'NIT',
        'CC' => 'cédula de ciudadanía',
        'CE' => 'cédula de extranjería',
        'Pasaporte' => 'pasaporte',
    ];

    // Abreviatura para bloques de firma (ej. "C.C. No. 123456789") — distinta
    // del label en prosa de arriba ("identificado con cédula de ciudadanía
    // No. ..."), a pedido explícito del usuario: la firma siempre usa la
    // abreviatura con puntos, nunca el texto completo ni "CC" sin puntos.
    public const DOCUMENT_TYPE_ABBREVIATIONS = [
        'NIT' => 'NIT',
        'CC' => 'C.C.',
        'CE' => 'C.E.',
        'Pasaporte' => 'Pasaporte',
    ];

    public static function documentTypeLabel(?string $type): string
    {
        return self::DOCUMENT_TYPE_LABELS[$type] ?? (string) $type;
    }

    public static function documentTypeAbbreviation(?string $type): string
    {
        return self::DOCUMENT_TYPE_ABBREVIATIONS[$type] ?? (string) $type;
    }

    public function namespace(): string
    {
        return 'cliente';
    }

    public function resolve(string $key, PlaceholderContext $context): ?string
    {
        $client = $context->client;
        if ($client === null) {
            return null;
        }

        return match ($key) {
            'nombre', 'razon_social' => $client->name,
            'identificacion' => $client->getFullDocumentAttribute(),
            'tipo_identificacion' => self::documentTypeLabel($client->document_type),
            'nit' => $client->document_type === 'NIT' ? $client->getFullDocumentAttribute() : null,
            'direccion' => $client->address,
            'ciudad' => $client->city,
            'departamento' => $client->department,
            'email' => $client->email,
            'telefono' => $client->phone,
            'contacto' => $client->contact_person,
            // Solo aplica a persona jurídica — quien firma el contrato es el representante
            // legal, no la empresa (ver PreambleClauseResolver, que además arma el preámbulo
            // completo distinto para natural vs. jurídica usando estos mismos datos).
            'representante_legal' => $client->legal_representative_name,
            'representante_legal_tipo_identificacion' => self::documentTypeLabel($client->legal_representative_document_type),
            'representante_legal_identificacion' => $client->legal_representative_document_number,
            'camara_comercio' => $client->chamber_of_commerce_city ?: $client->city,
            // Quien firma por EL CLIENTE: el representante legal si es persona jurídica
            // (la empresa misma no puede firmar), o el propio cliente si es natural — usado
            // en el bloque de firmas para que aparezca el nombre real de quien suscribe.
            // Nunca puede devolver null (PlaceholderEngine deja el placeholder literal si
            // no resuelve): si falta registrar el representante legal, se deja un mensaje
            // explícito en vez de un "{{...}}" roto en el documento — mismo criterio que
            // PreambleClauseResolver para el mismo dato faltante.
            'firmante_nombre' => $client->person_type === 'juridica'
                ? ($client->legal_representative_name ?: '[Falta registrar el representante legal]')
                : $client->name,
            // Abreviatura ("C.C.") para el bloque de firma — mismo criterio que
            // {{empresa.tipo_identificacion_abrev}} para el contador.
            'firmante_tipo_identificacion' => $client->person_type === 'juridica'
                ? self::documentTypeAbbreviation($client->legal_representative_document_type ?: 'CC')
                : self::documentTypeAbbreviation($client->document_type),
            'firmante_identificacion' => $client->person_type === 'juridica'
                ? ($client->legal_representative_document_number ?: '—')
                : $client->getFullDocumentAttribute(),
            default => null,
        };
    }
}
