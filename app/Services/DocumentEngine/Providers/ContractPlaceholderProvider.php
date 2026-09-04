<?php

namespace App\Services\DocumentEngine\Providers;

use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\PlaceholderProvider;

/**
 * Passthrough genérico sobre PlaceholderContext::$variables — las
 * respuestas del wizard (número, fecha, ciudad, valor...) más lo que los
 * ClauseResolver dinámicos inyectan vía withVariables() (ej. 'objeto',
 * 'duracion_texto', 'valor_letras'). No conoce la forma de un contrato
 * específico: cualquier tipo de documento futuro reutiliza este mismo
 * provider con sus propias keys.
 */
final class ContractPlaceholderProvider implements PlaceholderProvider
{
    public function namespace(): string
    {
        return 'contrato';
    }

    public function resolve(string $key, PlaceholderContext $context): ?string
    {
        $value = $context->variables[$key] ?? null;

        return $value !== null ? (string) $value : null;
    }
}
