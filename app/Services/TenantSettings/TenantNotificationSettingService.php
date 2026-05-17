<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantNotificationSetting;
use Illuminate\Database\Eloquent\Model;

final readonly class TenantNotificationSettingService extends BaseTenantSettingService
{
    protected function modelClass(): string
    {
        return TenantNotificationSetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.notification.updated';
    }

    protected function normalizeData(array $data, Model $setting): array
    {
        $data = parent::normalizeData($data, $setting);
        $data['admin_notification_emails'] = $this->linesToArray($data['admin_notification_emails'] ?? null);

        return $data;
    }

    protected function booleanFields(): array
    {
        return [
            'notify_admin_on_failed_jobs',
            'notify_admin_on_email_failure',
            'notify_admin_on_permission_change',
            'notify_admin_on_integration_failure',
        ];
    }

    /**
     * @return list<string>|null
     */
    private function linesToArray(mixed $value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: [])));
    }
}
