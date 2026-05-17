<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use App\Support\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract readonly class BaseTenantSettingService
{
    public function __construct(
        protected LogPersistenceService $logPersistenceService,
        protected TenantContext $tenantContext,
    ) {}

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * @return array<string, mixed>
     */
    abstract protected function defaults(): array;

    abstract protected function auditAction(): string;

    public function getOrCreateDefault(?int $userId = null): Model
    {
        $modelClass = $this->modelClass();
        $setting = $modelClass::query()->orderBy('id')->first();

        if ($setting instanceof Model) {
            return $setting;
        }

        return $modelClass::query()->create(array_merge($this->defaults(), [
            'created_by' => $userId,
            'updated_by' => $userId,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $authenticatedUser = auth()->user();
            $tenant = $this->tenantContext->require();
            $setting = $this->getOrCreateDefault(AuthenticatedUserId::resolve());
            $modelClass = $this->modelClass();
            $setting = $modelClass::query()->whereKey($setting->getKey())->lockForUpdate()->firstOrFail();
            $before = $this->auditSnapshot($setting);

            $setting->fill(array_merge($this->normalizeData($data, $setting), [
                'updated_by' => AuthenticatedUserId::resolve(),
            ]))->save();

            $this->logPersistenceService->logAudit(
                action: $this->auditAction(),
                auditableType: $modelClass,
                auditableId: (int) $setting->getKey(),
                beforeData: $this->withTenantAuditContext($before),
                afterData: $this->withTenantAuditContext($this->auditSnapshot($setting->refresh())),
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );

            return $setting;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeData(array $data, Model $setting): array
    {
        foreach ($this->booleanFields() as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    protected function booleanFields(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditSnapshot(Model $setting): array
    {
        return $setting->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withTenantAuditContext(array $data): array
    {
        $tenant = $this->tenantContext->require();

        return array_merge($data, [
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'schema_name' => $tenant->schema_name,
        ]);
    }
}
