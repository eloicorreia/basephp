<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            '_tenant_header' => ['nullable', 'string', 'max:100'],
            'tenant_code' => ['nullable', 'required_without:_tenant_header', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            '_tenant_header' => trim((string) $this->header('X-Tenant-Id', '')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_code.required_without' => 'O tenant é obrigatório para acessar o painel administrativo.',
        ];
    }
}
