<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantPasswordPolicy;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

final readonly class TenantPasswordPolicyService
{
    public function __construct(
        private LogPersistenceService $logPersistenceService,
        private TenantContext $tenantContext,
    ) {}

    public function getOrCreateDefault(?int $userId = null): TenantPasswordPolicy
    {
        $policy = TenantPasswordPolicy::query()
            ->orderByDesc('active')
            ->orderBy('id')
            ->first();

        if ($policy instanceof TenantPasswordPolicy) {
            return $policy;
        }

        return TenantPasswordPolicy::query()->create([
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): TenantPasswordPolicy
    {
        return DB::transaction(function () use ($data): TenantPasswordPolicy {
            $authenticatedUser = auth()->user();
            $tenant = $this->tenantContext->require();
            $policy = $this->getOrCreateDefault(AuthenticatedUserId::resolve());
            $policy = TenantPasswordPolicy::query()->whereKey($policy->id)->lockForUpdate()->firstOrFail();
            $before = array_merge($policy->toArray(), [
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
                'schema_name' => $tenant->schema_name,
            ]);

            $policy->fill([
                'min_length' => $data['min_length'],
                'max_length' => $data['max_length'],
                'require_uppercase' => (bool) ($data['require_uppercase'] ?? false),
                'require_lowercase' => (bool) ($data['require_lowercase'] ?? false),
                'require_numbers' => (bool) ($data['require_numbers'] ?? false),
                'require_symbols' => (bool) ($data['require_symbols'] ?? false),
                'disallow_common_passwords' => (bool) ($data['disallow_common_passwords'] ?? false),
                'disallow_user_personal_data' => (bool) ($data['disallow_user_personal_data'] ?? false),
                'password_expiration_days' => $data['password_expiration_days'] ?? null,
                'password_history_count' => $data['password_history_count'],
                'max_failed_attempts' => $data['max_failed_attempts'],
                'lockout_minutes' => $data['lockout_minutes'],
                'must_change_password_on_first_login' => (bool) ($data['must_change_password_on_first_login'] ?? false),
                'temporary_password_expiration_minutes' => $data['temporary_password_expiration_minutes'],
                'active' => (bool) ($data['active'] ?? false),
                'updated_by' => AuthenticatedUserId::resolve(),
            ])->save();

            $this->logPersistenceService->logAudit(
                action: 'tenant_settings.password_policy.updated',
                auditableType: TenantPasswordPolicy::class,
                auditableId: (int) $policy->id,
                beforeData: $before,
                afterData: array_merge($policy->fresh()?->toArray() ?? [], [
                    'tenant_id' => $tenant->id,
                    'tenant_code' => $tenant->code,
                    'schema_name' => $tenant->schema_name,
                ]),
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );

            return $policy->refresh();
        });
    }
}
