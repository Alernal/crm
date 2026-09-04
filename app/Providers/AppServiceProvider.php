<?php

namespace App\Providers;

use App\View\Composers\TaxAlertsComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (env('CODESPACES') === true) {
            URL::forceScheme('https');
        }

        // Vista de paginación propia, en español y con los tokens del sistema de
        // diseño — el Panel Admin queda fuera a propósito (mismo criterio que el
        // resto del rediseño visual) y sigue usando la vista de Tailwind de
        // Laravel explícitamente en admin/logs/index.blade.php.
        Paginator::defaultView('pagination.custom');

        // Campana de vencimientos tributarios del top bar — solo layouts.app
        // (el CRM normal); Panel Admin y el layout de Comunicación de equipo
        // usan sus propios layouts y no la reciben.
        View::composer('layouts.app', TaxAlertsComposer::class);
    }
}
