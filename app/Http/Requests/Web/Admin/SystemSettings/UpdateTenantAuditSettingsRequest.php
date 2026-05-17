<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantAuditSettingsRequest extends FormRequest
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
            'audit_enabled' => ['sometimes', 'boolean'],
            'audit_store_before_after' => ['sometimes', 'boolean'],
            'audit_payload_enabled' => ['sometimes', 'boolean'],
            'audit_sensitive_payload_masking' => ['sometimes', 'boolean'],
            'api_request_log_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'audit_log_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'integration_log_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'email_log_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'queue_log_retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
        ];
    }
}
