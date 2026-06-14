<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeClients = $user->clients()->where('status', 'active')->count();

        $pendingInvoices = $user->invoices()
            ->with('payments')
            ->whereIn('status', ['sent', 'overdue'])
            ->get();

        $receivable = $pendingInvoices->sum(
            fn($inv) => $inv->total - $inv->payments->sum('amount')
        );

        $collectedThisMonth = $user->payments()
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        $upcomingTaxEvents = $user->taxEvents()
            ->where('status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(15)])
            ->count();

        $recentInvoices = $user->invoices()
            ->with('client')
            ->latest()
            ->take(5)
            ->get();

        $nextTaxEvents = $user->taxEvents()
            ->with('client')
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->take(7)
            ->get();

        $overdueInvoices = $user->invoices()
            ->with('client')
            ->where('status', 'overdue')
            ->count();

        return view('dashboard', compact(
            'activeClients',
            'receivable',
            'collectedThisMonth',
            'upcomingTaxEvents',
            'recentInvoices',
            'nextTaxEvents',
            'overdueInvoices',
        ));
    }
}
