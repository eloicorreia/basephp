<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantIntegrationSetting;

final readonly class TenantIntegrationSettingService extends BaseTenantSettingService
{
    protected function modelClass(): string
    {
        return TenantIntegrationSetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.integration.updated';
    }

    protected function booleanFields(): array
    {
        return ['integration_enabled', 'circuit_breaker_enabled'];
    }
}
