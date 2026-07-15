<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:CC,CE,Pasaporte,PEP'],
            'document_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'contract_type' => ['required', 'in:' . implode(',', array_keys(Employee::CONTRACT_TYPES))],
            'hire_date' => ['required', 'date'],
            'termination_reason' => ['nullable', 'required_with:termination_date', 'in:' . implode(',', array_keys(Employee::TERMINATION_REASONS))],
            'termination_date' => ['nullable', 'required_with:termination_reason', 'date', 'after_or_equal:hire_date'],
            'base_salary' => ['required', 'numeric', 'min:0'],
            'salary_type' => ['required', 'in:fijo,variable'],
            'weekly_hours_override' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'transport_allowance_mode' => ['required', 'in:' . implode(',', array_keys(Employee::TRANSPORT_ALLOWANCE_MODES))],
            'transport_allowance_note' => ['nullable', 'string', 'max:255'],
            'eps' => ['nullable', 'string', 'max:255'],
            'afp' => ['nullable', 'string', 'max:255'],
            'arl_risk_level' => ['required', 'in:' . implode(',', array_keys(Employee::ARL_RISK_LEVELS))],
            'pension_exempt' => ['nullable', 'boolean'],
            'ccf' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:20'],
            'account_number' => ['nullable', 'string', 'max:30'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
