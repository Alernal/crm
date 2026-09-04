<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'nit'                     => ['nullable', 'string', 'max:20'],
            'identification_type'     => ['nullable', 'in:CC,CE,Pasaporte'],
            'identification_number'   => ['nullable', 'string', 'max:30'],
            'phone'                   => ['nullable', 'string', 'max:20'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'address'                 => ['nullable', 'string', 'max:255'],
            'professional_card_number' => ['nullable', 'string', 'max:50'],
            'logo'                 => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048', 'dimensions:min_width=840,min_height=336'],
            'bank_name'            => ['nullable', 'string', 'max:100'],
            'account_type'         => ['nullable', 'in:savings,checking'],
            'account_number'       => ['nullable', 'string', 'max:50'],
            'account_holder_name'  => ['nullable', 'string', 'max:150'],
            'account_holder_id'    => ['nullable', 'string', 'max:30'],
            'payment_link'         => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.dimensions' => 'El logo debe tener al menos 840x336 píxeles para que se vea nítido en las cuentas de cobro. Sube una versión de mayor resolución.',
        ];
    }
}
