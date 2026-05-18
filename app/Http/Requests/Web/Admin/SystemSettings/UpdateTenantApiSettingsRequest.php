<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $value = $this->input('api_allowed_origins');

                if (! is_string($value) || trim($value) === '') {
                    return;
                }

                foreach (preg_split('/\R/', $value) ?: [] as $line) {
                    $origin = trim($line);

                    if ($origin === '' || $origin === '*' || $this->isValidOrigin($origin)) {
                        continue;
                    }

                    $validator->errors()->add('api_allowed_origins', 'Informe apenas origens válidas, uma por linha.');

                    return;
                }
            },
        ];
    }

    private function isValidOrigin(string $origin): bool
    {
        if (filter_var($origin, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($origin, PHP_URL_SCHEME);
        $host = parse_url($origin, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && is_string($host) && $host !== '';
    }
}
