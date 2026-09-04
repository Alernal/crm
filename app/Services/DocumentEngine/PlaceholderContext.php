<?php

namespace App\Services\DocumentEngine;

use App\Models\Client;
use App\Models\User;

/**
 * Contexto inmutable con todos los datos disponibles para resolver
 * placeholders de un documento. Los ClauseResolver dinámicos (Objeto,
 * Duración, Pago) enriquecen `variables` antes de delegar la sustitución
 * final al PlaceholderEngine — ver withVariables().
 */
final class PlaceholderContext
{
    public function __construct(
        public readonly ?Client $client,
        public readonly User $company,
        public readonly array $variables = [],
    ) {
    }

    /** Retorna un nuevo contexto con variables adicionales fusionadas (nunca muta el actual). */
    public function withVariables(array $extra): self
    {
        return new self($this->client, $this->company, array_merge($this->variables, $extra));
    }
}
