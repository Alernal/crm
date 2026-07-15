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
            'phone'                   => ['nullable', 'string', 'max:20'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'address'                 => ['nullable', 'string', 'max:255'],
            'professional_card_number' => ['nullable', 'string', 'max:50'],
            'logo'                 => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'bank_name'            => ['nullable', 'string', 'max:100'],
            'account_type'         => ['nullable', 'in:savings,checking'],
            'account_number'       => ['nullable', 'string', 'max:50'],
            'account_holder_name'  => ['nullable', 'string', 'max:150'],
            'account_holder_id'    => ['nullable', 'string', 'max:30'],
            'payment_link'         => ['nullable', 'string', 'max:500'],
        ];
    }
}
