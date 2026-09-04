<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:team_members,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,member'],
            'channel_id' => ['nullable', 'integer'],
        ];
    }
}
