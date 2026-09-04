<?php

namespace App\Services\DocumentEngine\Providers;

use App\Services\DocumentEngine\PlaceholderContext;
use App\Services\DocumentEngine\PlaceholderProvider;
use Illuminate\Support\Facades\Storage;

final class SignaturePlaceholderProvider implements PlaceholderProvider
{
    public function namespace(): string
    {
        return 'firma';
    }

    public function resolve(string $key, PlaceholderContext $context): ?string
    {
        return match ($key) {
            // El consultor puede tener firma escaneada en su perfil; si no, queda la línea en blanco del template.
            'consultor' => $context->company->signature_path
                ? '<img src="'.Storage::disk('local')->url($context->company->signature_path).'" style="height:60px" alt="Firma">'
                : '',
            // El cliente firma físicamente el documento impreso — nunca hay imagen almacenada.
            'cliente' => '',
            default => null,
        };
    }
}
