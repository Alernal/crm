<?php

namespace App\Providers;

use App\Services\DocumentEngine\PlaceholderEngine;
use App\Services\DocumentEngine\Providers\CertificatePlaceholderProvider;
use App\Services\DocumentEngine\Providers\ClientPlaceholderProvider;
use App\Services\DocumentEngine\Providers\CompanyPlaceholderProvider;
use App\Services\DocumentEngine\Providers\ContractPlaceholderProvider;
use App\Services\DocumentEngine\Providers\ProposalPlaceholderProvider;
use App\Services\DocumentEngine\Providers\SignaturePlaceholderProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Registra el PlaceholderEngine como singleton con sus providers.
 * Un tipo de documento nuevo con placeholders genuinamente distintos solo
 * necesita agregar su provider aquí — el motor y los resolvers no cambian.
 */
class DocumentEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlaceholderEngine::class, function () {
            return (new PlaceholderEngine())
                ->register(new ClientPlaceholderProvider())
                ->register(new CompanyPlaceholderProvider())
                ->register(new ContractPlaceholderProvider())
                ->register(new ProposalPlaceholderProvider())
                ->register(new CertificatePlaceholderProvider())
                ->register(new SignaturePlaceholderProvider());
        });
    }
}
