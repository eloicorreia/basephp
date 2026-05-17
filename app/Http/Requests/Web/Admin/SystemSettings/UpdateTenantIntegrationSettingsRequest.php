<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantIntegrationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web') !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tenant' => ['required', 'string', 'max:100'],
            'integration_enabled' => ['sometimes', 'boolean'],
            'default_timeout_seconds' => ['required', 'integer', 'min:1', 'max:300'],
            'retry_attempts' => ['required', 'integer', 'min:0', 'max:20'],
            'retry_backoff_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
            'circuit_breaker_enabled' => ['sometimes', 'boolean'],
            'circuit_breaker_failure_threshold' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
