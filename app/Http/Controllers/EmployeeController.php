<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->employees()->with('client');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($clientId = $request->get('client_id')) {
            $query->where('client_id', $clientId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $employees = $query->orderBy('first_name')->paginate(20)->withQueryString();

        $totalActive = $request->user()->employees()->where('status', 'active')->count();
        $totalPayroll = $request->user()->employees()->where('status', 'active')->sum('base_salary');

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);

        return view('employees.index', compact('employees', 'totalActive', 'totalPayroll', 'clients'));
    }

    public function create(Request $request): View
    {
        $clients = $request->user()->clients()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $selectedClientId = $request->get('client_id');

        return view('employees.create', compact('clients', 'selectedClientId'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = array_merge($request->validated(), ['pension_exempt' => $request->boolean('pension_exempt')]);

        abort_unless(
            $request->user()->clients()->where('id', $data['client_id'])->exists(),
            403
        );

        $employee = $request->user()->employees()->create($data);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Empleado creado correctamente.');
    }

    public function show(Request $request, Employee $employee): View
    {
        abort_if($employee->user_id !== $request->user()->id, 403);

        $employee->load('client');
        $recentPayrolls = $employee->payrolls()->with('payrollPeriod')->latest('id')->take(6)->get();

        return view('employees.show', compact('employee', 'recentPayrolls'));
    }

    public function edit(Request $request, Employee $employee): View
    {
        abort_if($employee->user_id !== $request->user()->id, 403);

        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name']);

        return view('employees.edit', compact('employee', 'clients'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        abort_if($employee->user_id !== $request->user()->id, 403);

        $data = array_merge($request->validated(), ['pension_exempt' => $request->boolean('pension_exempt')]);

        abort_unless(
            $request->user()->clients()->where('id', $data['client_id'])->exists(),
            403
        );

        $employee->update($data);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(Request $request, Employee $employee): RedirectResponse
    {
        abort_if($employee->user_id !== $request->user()->id, 403);

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Empleado eliminado.');
    }
}
