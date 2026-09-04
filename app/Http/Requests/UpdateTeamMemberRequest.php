<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('team_members', 'email')->ignore($this->route('teamMember')),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,member'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
