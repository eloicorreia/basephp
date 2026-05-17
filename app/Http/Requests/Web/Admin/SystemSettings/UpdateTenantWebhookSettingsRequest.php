<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantWebhookSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user('web') !== null; }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tenant' => ['required', 'string', 'max:100'],
            'webhook_enabled' => ['sometimes', 'boolean'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'webhook_secret' => ['nullable', 'string', 'max:1000'],
            'webhook_events' => ['nullable', 'string', 'max:4000'],
            'webhook_retry_attempts' => ['required', 'integer', 'min:0', 'max:20'],
            'webhook_timeout_seconds' => ['required', 'integer', 'min:1', 'max:300'],
        ];
    }
}
