<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Service;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'unit'        => ['required', 'string', 'max:50'],
            'base_price'  => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'applies_vat' => ['boolean'],
            'status'      => ['required', 'in:active,inactive'],
        ];
    }
}
