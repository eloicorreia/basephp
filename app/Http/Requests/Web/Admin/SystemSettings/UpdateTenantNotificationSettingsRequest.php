<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('web') !== null; }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tenant' => ['required', 'string', 'max:100'],
            'notify_admin_on_failed_jobs' => ['sometimes', 'boolean'],
            'notify_admin_on_email_failure' => ['sometimes', 'boolean'],
            'notify_admin_on_permission_change' => ['sometimes', 'boolean'],
            'notify_admin_on_integration_failure' => ['sometimes', 'boolean'],
            'admin_notification_emails' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
