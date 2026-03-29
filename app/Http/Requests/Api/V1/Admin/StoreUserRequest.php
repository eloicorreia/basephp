<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:100', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
            'must_change_password' => ['sometimes', 'boolean'],
        ];
    }
}