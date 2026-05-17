<?php

declare(strict_types=1);

namespace App\Support\Tenant;

final class TenantRequiredTables
{
    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            'mail_configs',
            'tenant_password_policies',
            'user_password_histories',
            'tenant_system_settings',
            'tenant_security_settings',
            'tenant_api_settings',
            'tenant_queue_settings',
            'tenant_audit_settings',
            'tenant_integration_settings',
            'tenant_webhook_settings',
            'tenant_notification_settings',
        ];
    }
}
