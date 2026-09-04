<?php

namespace App\Services\DocumentEngine;

/**
 * Implementado por resolvers dinámicos (computed/builder) que necesitan
 * calcular variables derivadas (fechas, montos en letras, listas de
 * servicios) ANTES de que el documento se renderice. ClauseEngine ejecuta
 * todos los enrichers en una primera pasada sobre un contexto compartido
 * — así una variable calculada por la Cláusula de Duración (ej.
 * duracion_texto) también está disponible para la Cláusula de Pago, sin
 * que cada cláusula tenga que "saber" de las demás.
 */
interface ClauseContextEnricher
{
    public function enrich(ResolvableClause $clause, PlaceholderContext $context): PlaceholderContext;
}
