<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\Security;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSecurityRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', 'unique:roles,code'],
            'name' => ['required', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
