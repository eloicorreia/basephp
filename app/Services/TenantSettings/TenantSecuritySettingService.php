<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantSecuritySetting;
use Illuminate\Database\Eloquent\Model;

final readonly class TenantSecuritySettingService extends BaseTenantSettingService
{
    protected function modelClass(): string
    {
        return TenantSecuritySetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.security.updated';
    }

    protected function normalizeData(array $data, Model $setting): array
    {
        $data = parent::normalizeData($data, $setting);
        $data['allowed_ip_ranges'] = $this->linesToArray($data['allowed_ip_ranges'] ?? null);

        return $data;
    }

    protected function booleanFields(): array
    {
        return [
            'force_single_session_per_user',
            'logout_on_password_change',
            'unlock_requires_admin',
            'notify_user_on_failed_login',
            'notify_admin_on_lockout',
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
