<?php

namespace App\Services\DocumentEngine;

/**
 * Un proveedor resuelve todos los placeholders de un namespace
 * ({{namespace.key}}). Agregar un tipo de documento nuevo con datos
 * nuevos NO requiere tocar el PlaceholderEngine — solo un provider nuevo
 * (o keys nuevas en uno existente).
 */
interface PlaceholderProvider
{
    /** Namespace que resuelve este provider, ej. 'cliente', 'empresa', 'contrato', 'firma'. */
    public function namespace(): string;

    /** Retorna null (nunca inventa datos) si la key no existe en este contexto. */
    public function resolve(string $key, PlaceholderContext $context): ?string;
}
