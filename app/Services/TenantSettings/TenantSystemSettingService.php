<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantSystemSetting;

final readonly class TenantSystemSettingService extends BaseTenantSettingService
{
    protected function modelClass(): string
    {
        return TenantSystemSetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.general.updated';
    }

    protected function booleanFields(): array
    {
        return ['maintenance_mode'];
    }
}
