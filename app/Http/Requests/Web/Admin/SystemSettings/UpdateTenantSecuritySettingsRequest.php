<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

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
}
