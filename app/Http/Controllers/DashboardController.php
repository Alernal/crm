<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const MONTH_ABBR = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public function index(Request $request): View
    {
        $user   = $request->user();
        $userId = $user->id;
        $today  = Carbon::today();

        Invoice::syncOverdueForUser($userId);

        $activeClients = $user->clients()->where('status', 'active')->count();

        // Cartera pendiente: SUM en SQL — no carga facturas en memoria
        $receivable = (float) DB::scalar("
            SELECT COALESCE(
                SUM(i.total) - SUM(COALESCE(p.paid, 0)), 0
            )
            FROM invoices i
            LEFT JOIN (
                SELECT invoice_id, SUM(amount) AS paid
                FROM payments
                GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            WHERE i.user_id = ?
              AND i.status IN ('sent', 'overdue')
        ", [$userId]);

        $collectedThisMonth = $user->payments()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $collectedLastMonth = $user->payments()
            ->whereMonth('payment_date', now()->subMonthNoOverflow()->month)
            ->whereYear('payment_date', now()->subMonthNoOverflow()->year)
            ->sum('amount');

        $collectedTrendPct = $collectedLastMonth > 0
            ? (($collectedThisMonth - $collectedLastMonth) / $collectedLastMonth) * 100
            : null;

        $upcomingTaxEvents = $user->taxEvents()
            ->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(15)])
            ->count();

        $recentInvoices = $user->invoices()
            ->with('client:id,name')
            ->latest()
            ->take(5)
            ->get();

        $nextTaxEvents = $user->taxEvents()
            ->with('client:id,name')
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->take(7)
            ->get();

        // ── Antigüedad de cartera (mismos rangos que el módulo Cartera) ──
        $agingRows = DB::select("
            SELECT i.due_date,
                   i.total - COALESCE(p.paid, 0) AS balance
            FROM invoices i
            LEFT JOIN (
                SELECT invoice_id, SUM(amount) AS paid
                FROM payments
                GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            WHERE i.user_id = ?
              AND i.status IN ('sent', 'overdue')
              AND i.total - COALESCE(p.paid, 0) > 0.01
        ", [$userId]);

        $aging = ['al_dia' => 0.0, 'dias_1_30' => 0.0, 'dias_31_60' => 0.0, 'dias_mas60' => 0.0];
        foreach ($agingRows as $row) {
            $saldo = (float) $row->balance;
            if (! $row->due_date) {
                $aging['al_dia'] += $saldo;
                continue;
            }
            $diff = $today->diffInDays(Carbon::parse($row->due_date), false);
            if ($diff >= 0) {
                $aging['al_dia'] += $saldo;
            } elseif ($diff >= -30) {
                $aging['dias_1_30'] += $saldo;
            } elseif ($diff >= -60) {
                $aging['dias_31_60'] += $saldo;
            } else {
                $aging['dias_mas60'] += $saldo;
            }
        }

        $overdueBalance = $aging['dias_1_30'] + $aging['dias_31_60'] + $aging['dias_mas60'];
        $overdueRatio = $receivable > 0 ? ($overdueBalance / $receivable) * 100 : 0.0;

        // ── Días promedio de cobro (facturas pagadas: emisión -> último pago) ──
        $avgCollectionRow = DB::selectOne("
            SELECT AVG(julianday(p.last_payment) - julianday(i.issue_date)) AS avg_days
            FROM invoices i
            JOIN (
                SELECT invoice_id, MAX(payment_date) AS last_payment
                FROM payments
                GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            WHERE i.user_id = ?
              AND i.status = 'paid'
              AND i.issue_date IS NOT NULL
        ", [$userId]);
        $avgCollectionDays = $avgCollectionRow?->avg_days !== null ? (int) round((float) $avgCollectionRow->avg_days) : null;

        // ── Facturado vs. cobrado, últimos 6 meses ──
        $rangeStart = now()->copy()->subMonths(5)->startOfMonth();

        $invoicedByMonth = DB::table('invoices')
            ->selectRaw("strftime('%Y-%m', issue_date) as ym, SUM(total) as total")
            ->where('user_id', $userId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where('issue_date', '>=', $rangeStart)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $collectedByMonth = DB::table('payments')
            ->selectRaw("strftime('%Y-%m', payment_date) as ym, SUM(amount) as total")
            ->where('user_id', $userId)
            ->where('payment_date', '>=', $rangeStart)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $monthlyTrend = collect(range(5, 0))->map(function ($monthsAgo) use ($invoicedByMonth, $collectedByMonth) {
            $date = now()->copy()->subMonths($monthsAgo);
            $ym = $date->format('Y-m');

            return [
                'label' => self::MONTH_ABBR[$date->month - 1],
                'invoiced' => round((float) ($invoicedByMonth[$ym] ?? 0)),
                'collected' => round((float) ($collectedByMonth[$ym] ?? 0)),
            ];
        })->values();

        return view('dashboard', compact(
            'activeClients',
            'receivable',
            'collectedThisMonth',
            'collectedTrendPct',
            'upcomingTaxEvents',
            'recentInvoices',
            'nextTaxEvents',
            'aging',
            'overdueRatio',
            'avgCollectionDays',
            'monthlyTrend',
        ));
    }
}
