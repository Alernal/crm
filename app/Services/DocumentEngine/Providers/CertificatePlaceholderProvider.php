<?php

namespace App\Services\DocumentEngine\Providers;

use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\PlaceholderProvider;

/**
 * Passthrough genérico sobre PlaceholderContext::$variables — mismo
 * patrón exacto que ContractPlaceholderProvider/ProposalPlaceholderProvider,
 * bajo el namespace 'certificado'.
 */
final class CertificatePlaceholderProvider implements PlaceholderProvider
{
    public function namespace(): string
    {
        return 'certificado';
    }

    public function resolve(string $key, PlaceholderContext $context): ?string
    {
        $value = $context->variables[$key] ?? null;

        return $value !== null ? (string) $value : null;
    }
}
