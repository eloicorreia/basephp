<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantApiSettingsRequest extends FormRequest
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
            'api_enabled' => ['sometimes', 'boolean'],
            'api_rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:100000'],
            'strict_rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:100000'],
            'login_rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:1000'],
            'api_default_pagination_size' => ['required', 'integer', 'min:1', 'max:1000'],
            'api_max_pagination_size' => ['required', 'integer', 'min:1', 'max:5000', 'gte:api_default_pagination_size'],
            'api_require_correlation_id' => ['sometimes', 'boolean'],
            'api_allowed_origins' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
