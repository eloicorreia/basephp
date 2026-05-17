<?php

declare(strict_types=1);

namespace App\Http\Requests\Web\Admin\SystemSettings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTenantQueueSettingsRequest extends FormRequest
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
            'default_queue' => ['required', 'string', 'max:80'],
            'email_queue' => ['required', 'string', 'max:80'],
            'max_job_attempts' => ['required', 'integer', 'min:1', 'max:50'],
            'job_retry_delay_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'failed_job_notify_admin' => ['sometimes', 'boolean'],
            'queue_processing_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
