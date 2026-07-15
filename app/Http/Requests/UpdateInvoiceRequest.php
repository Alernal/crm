<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'              => ['required', 'integer', 'exists:clients,id'],
            'issue_date'             => ['required', 'date'],
            'due_date'               => ['nullable', 'date', 'after_or_equal:issue_date'],
            'payment_method'         => ['nullable', 'in:cash,bank_transfer,check,bre_b'],
            'withholding_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.description'    => ['required', 'string', 'max:255'],
            'items.*.service_id'     => ['nullable', 'integer', 'exists:services,id'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount_rate'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.vat_rate'       => ['required', 'in:0,19'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'           => 'Debes seleccionar un cliente.',
            'client_id.exists'             => 'El cliente seleccionado no es válido.',
            'issue_date.required'          => 'La fecha de emisión es obligatoria.',
            'issue_date.date'              => 'La fecha de emisión no es válida.',
            'due_date.date'                => 'La fecha de vencimiento no es válida.',
            'due_date.after_or_equal'      => 'La fecha de vencimiento debe ser igual o posterior a la fecha de emisión.',
            'items.required'               => 'Debes agregar al menos un ítem.',
            'items.min'                    => 'Debes agregar al menos un ítem.',
            'items.*.description.required' => 'La descripción del ítem es obligatoria.',
            'items.*.quantity.required'    => 'La cantidad es obligatoria.',
            'items.*.unit_price.required'  => 'El precio unitario es obligatorio.',
            'items.*.discount_rate.max'    => 'El descuento no puede superar el 100%.',
            'withholding_rate.max'         => 'La retefuente no puede superar el 100%.',
        ];
    }
}
