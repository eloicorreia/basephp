<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}