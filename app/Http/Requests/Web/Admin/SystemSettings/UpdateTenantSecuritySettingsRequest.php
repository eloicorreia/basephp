<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateTenantSecuritySettingsRequest extends FormRequest
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
            'session_lifetime_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'idle_timeout_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'force_single_session_per_user' => ['sometimes', 'boolean'],
            'logout_on_password_change' => ['sometimes', 'boolean'],
            'max_login_attempts' => ['required', 'integer', 'min:1', 'max:50'],
            'lockout_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'unlock_requires_admin' => ['sometimes', 'boolean'],
            'notify_user_on_failed_login' => ['sometimes', 'boolean'],
            'notify_admin_on_lockout' => ['sometimes', 'boolean'],
            'allowed_ip_ranges' => ['nullable', 'string', 'max:4000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $value = $this->input('allowed_ip_ranges');

                if (! is_string($value) || trim($value) === '') {
                    return;
                }

                foreach (preg_split('/\R/', $value) ?: [] as $line) {
                    $range = trim($line);

                    if ($range === '' || $this->isValidIpRange($range)) {
                        continue;
                    }

                    $validator->errors()->add('allowed_ip_ranges', 'Informe apenas IPs ou CIDRs válidos, um por linha.');

                    return;
                }
            },
        ];
    }

    private function isValidIpRange(string $range): bool
    {
        if (filter_var($range, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        if (! str_contains($range, '/')) {
            return false;
        }

        [$ip, $prefix] = array_pad(explode('/', $range, 2), 2, null);

        if (! is_string($prefix) || ! ctype_digit($prefix) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        $prefixLength = (int) $prefix;
        $maxPrefix = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? 128 : 32;

        return $prefixLength >= 0 && $prefixLength <= $maxPrefix;
    }
}
