<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantQueueSetting;

final readonly class TenantQueueSettingService extends BaseTenantSettingService
{
    protected function modelClass(): string
    {
        return TenantQueueSetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.queue.updated';
    }

    protected function booleanFields(): array
    {
        return ['failed_job_notify_admin', 'queue_processing_enabled'];
    }
}
