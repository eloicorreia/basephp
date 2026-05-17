<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantAuditSetting;

final readonly class TenantAuditSettingService extends BaseTenantSettingService
{
    protected function modelClass(): string
    {
        return TenantAuditSetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.audit.updated';
    }

    protected function booleanFields(): array
    {
        return [
            'audit_enabled',
            'audit_store_before_after',
            'audit_payload_enabled',
            'audit_sensitive_payload_masking',
        ];
    }
}
