<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'document_type'        => ['required', 'in:NIT,CC,CE,Pasaporte'],
            'document_number'      => ['required', 'string', 'max:20'],
            'dv'                   => ['nullable', 'string', 'max:1'],
            'person_type'          => ['required', 'in:natural,juridica'],
            'tax_regime'           => ['required', 'in:gran_contribuyente,autorretenedor,agente_retencion_iva,regimen_simple,no_aplica'],
            'tax_responsibilities' => ['nullable', 'array'],
            'tax_responsibilities.*' => ['string', 'exists:tax_obligation_types,code'],
            'email'                => ['nullable', 'email', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:20'],
            'address'              => ['nullable', 'string', 'max:255'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'department'           => ['nullable', 'string', 'max:100'],
            'contact_person'       => ['nullable', 'string', 'max:255'],
            'legal_representative_name'             => ['nullable', 'string', 'max:255'],
            'legal_representative_document_type'    => ['nullable', 'in:CC,CE,Pasaporte'],
            'legal_representative_document_number'  => ['nullable', 'string', 'max:20'],
            'chamber_of_commerce_city'               => ['nullable', 'string', 'max:100'],
            'status'               => ['required', 'in:active,inactive'],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'invoice_prefix'       => ['nullable', 'string', 'max:20'],
            'invoice_consecutive'  => ['nullable', 'integer', 'min:0'],
            'payroll_periodicity'  => ['nullable', 'in:mensual,quincenal'],
            'payroll_prefix'       => ['nullable', 'string', 'max:20'],
            'payroll_consecutive'  => ['nullable', 'integer', 'min:0'],
            'payroll_pila_exempt'  => ['nullable', 'boolean'],
        ];
    }
}
