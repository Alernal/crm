<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->clients();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($regime = $request->get('regime')) {
            $query->where('tax_regime', $regime);
        }

        $clients       = $query->orderBy('name')->paginate(20)->withQueryString();
        $totalActive   = $request->user()->clients()->where('status', 'active')->count();
        $totalInactive = $request->user()->clients()->where('status', 'inactive')->count();

        return view('clients.index', compact('clients', 'totalActive', 'totalInactive'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $request->user()->clients()->create($request->validated());

        return redirect()->route('clients.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Request $request, Client $client): View
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $client->load(['invoices.payments', 'taxEvents']);

        $pendingInvoices = $client->invoices->whereIn('status', ['sent', 'overdue']);
        $pendingBalance  = $pendingInvoices->sum(fn($i) => $i->total - $i->payments->sum('amount'));
        $recentInvoices  = $client->invoices->sortByDesc('created_at')->take(5);
        $upcomingEvents  = $client->taxEvents
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->sortBy('due_date')
            ->take(5);

        return view('clients.show', compact(
            'client', 'pendingBalance', 'recentInvoices', 'upcomingEvents'
        ));
    }

    public function edit(Request $request, Client $client): View
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        return view('clients.edit', compact('client'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $client->update($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Cliente eliminado.');
    }
}
