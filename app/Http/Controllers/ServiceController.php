<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->services()->with('category');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->get('vat') !== null && $request->get('vat') !== '') {
            $query->where('applies_vat', (bool) $request->get('vat'));
        }

        $services      = $query->orderBy('consecutive_number')->paginate(20)->withQueryString();
        $totalActive   = $request->user()->services()->where('status', 'active')->count();
        $totalInactive = $request->user()->services()->where('status', 'inactive')->count();
        $categories    = $request->user()->serviceCategories()->orderBy('name')->get();

        return view('services.index', compact('services', 'totalActive', 'totalInactive', 'categories'));
    }

    public function create(Request $request): View
    {
        $categories = $request->user()->serviceCategories()->orderBy('name')->get();

        return view('services.create', compact('categories'));
    }

    public function store(StoreServiceRequest $request): RedirectResponse|JsonResponse
    {
        $service = DB::transaction(function () use ($request) {
            $owner = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $owner->increment('service_consecutive');

            return $request->user()->services()->create($request->validated() + [
                'consecutive_number' => $owner->service_consecutive,
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json($service->load('category'), 201);
        }

        return redirect()->route('services.index')
            ->with('success', 'Servicio creado correctamente.');
    }

    public function show(Request $request, Service $service): View
    {
        abort_if($service->user_id !== $request->user()->id, 403);

        $service->load('category');

        return view('services.show', compact('service'));
    }

    public function edit(Request $request, Service $service): View
    {
        abort_if($service->user_id !== $request->user()->id, 403);

        $categories = $request->user()->serviceCategories()->orderBy('name')->get();

        return view('services.edit', compact('service', 'categories'));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        abort_if($service->user_id !== $request->user()->id, 403);

        $service->update($request->validated());

        return redirect()->route('services.show', $service)
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        abort_if($service->user_id !== $request->user()->id, 403);

        $service->delete();

        return redirect()->route('services.index')
            ->with('success', 'Servicio eliminado.');
    }
}
