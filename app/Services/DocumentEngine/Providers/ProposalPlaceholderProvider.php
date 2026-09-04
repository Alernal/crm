<?php

namespace App\Services\DocumentEngine\Providers;

use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\PlaceholderProvider;

/**
 * Passthrough genérico sobre PlaceholderContext::$variables — mismo
 * patrón exacto que ContractPlaceholderProvider, solo que bajo el
 * namespace 'propuesta'. Ambos leen la misma bolsa de variables
 * compartida, así que un resolver como ServicesObjectClauseResolver
 * (que escribe 'objeto') es visible desde cualquiera de los dos.
 */
final class ProposalPlaceholderProvider implements PlaceholderProvider
{
    public function namespace(): string
    {
        return 'propuesta';
    }

    public function resolve(string $key, PlaceholderContext $context): ?string
    {
        $value = $context->variables[$key] ?? null;

        return $value !== null ? (string) $value : null;
    }
}
