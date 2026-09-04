<?php

namespace App\Services\DocumentEngine\Providers;

use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\PlaceholderProvider;

final class CompanyPlaceholderProvider implements PlaceholderProvider
{
    public function namespace(): string
    {
        return 'empresa';
    }

    public function resolve(string $key, PlaceholderContext $context): ?string
    {
        $company = $context->company;

        return match ($key) {
            'nombre', 'razon_social' => $company->name,
            'nit' => $company->nit,
            // La cédula es la identificación PERSONAL de EL CONSULTOR (preámbulo y firma
            // dicen "identificado con [tipo] No. [número]", que legalmente debe ser un
            // documento de identidad — el NIT es un registro tributario, nunca corresponde
            // aquí, ni siquiera como reserva). El tipo siempre es CC/CE/Pasaporte, nunca
            // NIT; si el número dedicado aún no está diligenciado, se reutiliza el NIT como
            // VALOR (en Colombia el NIT de persona natural son los mismos dígitos de la
            // cédula) pero jamás se relabela como "NIT".
            'identificacion' => $company->identification_number ?: $company->nit,
            'tipo_identificacion' => ClientPlaceholderProvider::documentTypeLabel($company->identification_type ?: 'CC'),
            // Igual que arriba, pero abreviado ("C.C.") — para usar en bloques de
            // firma, nunca en prosa (ver ClientPlaceholderProvider::DOCUMENT_TYPE_ABBREVIATIONS).
            'tipo_identificacion_abrev' => ClientPlaceholderProvider::documentTypeAbbreviation($company->identification_type ?: 'CC'),
            'direccion' => $company->address,
            'ciudad' => $company->city,
            'email' => $company->email,
            'telefono' => $company->phone,
            'tarjeta_profesional' => $company->professional_card_number,
            'representante_legal' => $company->name,
            'banco' => $company->bank_name,
            'numero_cuenta' => $company->account_number,
            'titular_cuenta' => $company->account_holder_name,
            'titular_cedula' => $company->account_holder_id ? number_format((int) $company->account_holder_id, 0, ',', '.') : null,
            default => null,
        };
    }
}
