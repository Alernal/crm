<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
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
            'tax_responsibilities.*' => ['string'],
            'email'                => ['nullable', 'email', 'max:255'],
            'phone'                => ['nullable', 'string', 'max:20'],
            'address'              => ['nullable', 'string', 'max:255'],
            'city'                 => ['nullable', 'string', 'max:100'],
            'department'           => ['nullable', 'string', 'max:100'],
            'contact_person'       => ['nullable', 'string', 'max:255'],
            'status'               => ['required', 'in:active,inactive'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ];
    }
}
