<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantApiSetting;
use Illuminate\Database\Eloquent\Model;

final readonly class TenantApiSettingService extends BaseTenantSettingService
{
    protected function modelClass(): string
    {
        return TenantApiSetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.api.updated';
    }

    protected function normalizeData(array $data, Model $setting): array
    {
        $data = parent::normalizeData($data, $setting);
        $data['api_allowed_origins'] = $this->linesToArray($data['api_allowed_origins'] ?? null);

        return $data;
    }

    protected function booleanFields(): array
    {
        return ['api_enabled', 'api_require_correlation_id'];
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
