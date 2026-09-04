<?php

namespace App\View\Composers;

use App\Services\TaxCalendarService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Alimenta la campana de notificaciones del top bar (`layouts.app`) con los
 * vencimientos tributarios pendientes de los próximos 30 días — agrupados
 * por cliente, porque con muchos vencimientos una lista evento por evento
 * es difícil de escanear (a pedido del usuario). Usa el mismo
 * TaxCalendarService::combinedEvents() (manual + calculado) que
 * TaxCalendarController::allCalendarItems()/index() — antes esta campana
 * solo contaba TaxEvent manuales y por eso su conteo de "vencidos" no
 * coincidía con el de la tabla año-completo (que solo mostraba lo calculado).
 */
class TaxAlertsComposer
{
    public function compose(View $view): void
    {
        $user = Auth::guard('web')->user();

        if (! $user) {
            $view->with([
                'taxAlerts' => collect(),
                'taxAlertsOverdueClientCount' => 0,
                'taxAlertsTotalClientCount' => 0,
            ]);

            return;
        }

        $service     = new TaxCalendarService();
        $currentYear = now()->year;
        $horizonYear = now()->addDays(30)->year;

        $alerts = $user->clients()
            ->where('status', 'active')
            ->get(['id', 'name'])
            ->map(function ($client) use ($service, $currentYear, $horizonYear) {
                $items = $service->combinedEvents($client, $currentYear);
                if ($horizonYear !== $currentYear) {
                    $items = $items->concat($service->combinedEvents($client, $horizonYear));
                }

                $relevant = $items
                    ->filter(fn ($e) => $e['status'] !== 'completed' && $e['due_date']->lte(now()->addDays(30)))
                    ->unique('id');

                if ($relevant->isEmpty()) {
                    return null;
                }

                $withStatus = $relevant->map(fn ($e) => [
                    'event' => $e,
                    'isOverdue' => $e['status'] === 'overdue',
                ]);

                $next = $withStatus->sortBy(fn ($item) => $item['event']['due_date'])->first();

                return [
                    'client' => $client,
                    'overdueCount' => $withStatus->where('isOverdue', true)->count(),
                    'upcomingCount' => $withStatus->where('isOverdue', false)->count(),
                    'nextEvent' => (object) [
                        'title' => $next['event']['title'],
                        'due_date' => $next['event']['due_date'],
                    ],
                    'nextIsOverdue' => $next['isOverdue'],
                ];
            })
            ->filter()
            ->sortBy(fn ($alert) => ($alert['overdueCount'] > 0 ? '0' : '1').$alert['nextEvent']->due_date->format('Y-m-d'))
            ->values();

        $view->with([
            'taxAlerts' => $alerts,
            'taxAlertsOverdueClientCount' => $alerts->filter(fn ($alert) => $alert['overdueCount'] > 0)->count(),
            'taxAlertsTotalClientCount' => $alerts->count(),
        ]);
    }
}
