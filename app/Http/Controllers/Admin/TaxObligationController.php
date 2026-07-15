<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\TaxObligationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxObligationController extends Controller
{
    public function index(): View
    {
        $obligations = TaxObligationType::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.tax-obligations.index', compact('obligations'));
    }

    public function create(): View
    {
        return view('admin.tax-obligations.create', [
            'periodicities' => TaxObligationType::$periodicities,
            'nitReferences' => TaxObligationType::$nitReferences,
            'regimes'       => TaxObligationType::$regimes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'               => 'required|string|max:32|unique:tax_obligation_types,code|alpha_dash',
            'name'               => 'required|string|max:128',
            'description'        => 'nullable|string|max:500',
            'periodicity'        => 'required|in:' . implode(',', array_keys(TaxObligationType::$periodicities)),
            'nit_reference'      => 'required|in:' . implode(',', array_keys(TaxObligationType::$nitReferences)),
            'regime'             => 'required|in:ordinario,simple,ambos',
            'responsibility_key' => 'nullable|string|max:64',
            'sort_order'         => 'nullable|integer|min:0',
            'active'             => 'boolean',
        ]);

        $data['code']   = strtoupper($data['code']);
        $data['active'] = $request->boolean('active', true);

        $ob = TaxObligationType::create($data);
        AdminLog::record('created', 'tax-obligations', $ob->id, [], $ob->toArray());

        return redirect()->route('admin.tax-obligations.index')
            ->with('success', "Obligación \"{$ob->name}\" creada.");
    }

    public function edit(TaxObligationType $taxObligation): View
    {
        return view('admin.tax-obligations.edit', [
            'obligation'    => $taxObligation,
            'periodicities' => TaxObligationType::$periodicities,
            'nitReferences' => TaxObligationType::$nitReferences,
            'regimes'       => TaxObligationType::$regimes,
        ]);
    }

    public function update(Request $request, TaxObligationType $taxObligation): RedirectResponse
    {
        $data = $request->validate([
            'code'               => 'required|string|max:32|alpha_dash|unique:tax_obligation_types,code,' . $taxObligation->id,
            'name'               => 'required|string|max:128',
            'description'        => 'nullable|string|max:500',
            'periodicity'        => 'required|in:' . implode(',', array_keys(TaxObligationType::$periodicities)),
            'nit_reference'      => 'required|in:' . implode(',', array_keys(TaxObligationType::$nitReferences)),
            'regime'             => 'required|in:ordinario,simple,ambos',
            'responsibility_key' => 'nullable|string|max:64',
            'sort_order'         => 'nullable|integer|min:0',
            'active'             => 'boolean',
        ]);

        $old = $taxObligation->toArray();
        $data['code']   = strtoupper($data['code']);
        $data['active'] = $request->boolean('active', true);

        $taxObligation->update($data);
        AdminLog::record('updated', 'tax-obligations', $taxObligation->id, $old, $taxObligation->fresh()->toArray());

        return redirect()->route('admin.tax-obligations.index')
            ->with('success', "Obligación \"{$taxObligation->name}\" actualizada.");
    }

    public function destroy(TaxObligationType $taxObligation): RedirectResponse
    {
        AdminLog::record('deleted', 'tax-obligations', $taxObligation->id, $taxObligation->toArray(), []);
        $taxObligation->delete();

        return redirect()->route('admin.tax-obligations.index')
            ->with('success', 'Obligación tributaria eliminada.');
    }
}
