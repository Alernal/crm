<?php

namespace App\Services\DocumentEngine;

/**
 * Motor de reemplazo genérico y reutilizable para CUALQUIER tipo de
 * documento — no contiene ninguna lógica específica de contratos. Un
 * documento nuevo (NDA, poder, acta...) reutiliza este mismo motor sin
 * cambios: solo necesita placeholders resueltos por los providers ya
 * registrados, o un provider nuevo si el namespace es genuinamente distinto.
 */
final class PlaceholderEngine
{
    /** @var array<string, PlaceholderProvider> */
    private array $providers = [];

    public function register(PlaceholderProvider $provider): self
    {
        $this->providers[$provider->namespace()] = $provider;

        return $this;
    }

    /**
     * Sustituye cada {{namespace.key}} encontrado en $html. Si un
     * placeholder no puede resolverse (namespace desconocido o el
     * provider retorna null) se deja tal cual — el motor nunca inventa
     * datos ni falla en silencio ocultando información faltante.
     */
    public function render(string $html, PlaceholderContext $context): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-z_]+)\.([a-z0-9_]+)\s*\}\}/i',
            function (array $matches) use ($context) {
                [$full, $namespace, $key] = $matches;

                $provider = $this->providers[$namespace] ?? null;
                $resolved = $provider?->resolve($key, $context);

                return $resolved ?? $full;
            },
            $html
        );
    }
}
