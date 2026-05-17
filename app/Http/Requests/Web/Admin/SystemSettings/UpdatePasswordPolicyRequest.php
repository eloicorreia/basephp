<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePasswordPolicyRequest extends FormRequest
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
            'tenant' => ['required', 'string', 'max:100'],
            'min_length' => ['required', 'integer', 'min:6', 'max:255'],
            'max_length' => ['required', 'integer', 'min:6', 'max:255', 'gte:min_length'],
            'require_uppercase' => ['sometimes', 'boolean'],
            'require_lowercase' => ['sometimes', 'boolean'],
            'require_numbers' => ['sometimes', 'boolean'],
            'require_symbols' => ['sometimes', 'boolean'],
            'disallow_common_passwords' => ['sometimes', 'boolean'],
            'disallow_user_personal_data' => ['sometimes', 'boolean'],
            'password_expiration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'password_history_count' => ['required', 'integer', 'min:0', 'max:50'],
            'max_failed_attempts' => ['required', 'integer', 'min:1', 'max:50'],
            'lockout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'must_change_password_on_first_login' => ['sometimes', 'boolean'],
            'temporary_password_expiration_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
