<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantWebhookSetting;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;

final readonly class TenantWebhookSettingService extends BaseTenantSettingService
{
    public function __construct(
        \App\Services\Logging\LogPersistenceService $logPersistenceService,
        \App\Support\Tenant\TenantContext $tenantContext,
        private Encrypter $encrypter,
    ) {
        parent::__construct($logPersistenceService, $tenantContext);
    }

    protected function modelClass(): string
    {
        return TenantWebhookSetting::class;
    }

    protected function defaults(): array
    {
        return [];
    }

    protected function auditAction(): string
    {
        return 'tenant_settings.webhook.updated';
    }

    protected function normalizeData(array $data, Model $setting): array
    {
        $data = parent::normalizeData($data, $setting);
        $secret = (string) ($data['webhook_secret'] ?? '');
        unset($data['webhook_secret']);

        $data['webhook_events'] = $this->linesToArray($data['webhook_events'] ?? null);

        if ($secret !== '') {
            $data['webhook_secret_encrypted'] = $this->encrypter->encryptString($secret);
        }

        return $data;
    }

    protected function booleanFields(): array
    {
        return ['webhook_enabled'];
    }

    protected function auditSnapshot(Model $setting): array
    {
        $data = $setting->toArray();
        $data['webhook_secret_encrypted'] = $setting->getAttribute('webhook_secret_encrypted') !== null ? '***' : null;

        return $data;
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
