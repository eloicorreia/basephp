<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSecurityPermissionRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', 'unique:permissions,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:80'],
            'context' => ['required', 'string', Rule::in(['api', 'web', 'both'])],
            'is_sensitive' => ['sometimes', 'boolean'],
        ];
    }
}
