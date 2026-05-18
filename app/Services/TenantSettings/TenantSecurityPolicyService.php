<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\Tenant;
use App\Models\TenantSecuritySetting;
use App\Models\TenantUserSecurityState;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class TenantSecurityPolicyService
{
    public function __construct(
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function registerSuccessfulAuthentication(User $user, Tenant $tenant, ?string $ip = null): void
    {
        DB::transaction(function () use ($user, $tenant, $ip): void {
            $state = $this->stateForUpdate($user, $tenant);

            $state->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => null,
                'locked_by_admin' => false,
                'last_successful_login_at' => now(),
                'last_successful_login_ip' => $ip,
            ])->save();
        });
    }

    public function registerFailedAuthentication(User $user, Tenant $tenant, TenantSecuritySetting $settings, ?string $ip = null): void
    {
        $state = DB::transaction(function () use ($user, $tenant, $settings, $ip): TenantUserSecurityState {
            $state = $this->stateForUpdate($user, $tenant);
            $attempts = (int) $state->failed_login_attempts + 1;
            $payload = [
                'failed_login_attempts' => $attempts,
                'last_failed_login_at' => now(),
                'last_failed_login_ip' => $ip,
            ];
            $maxAttempts = max(1, (int) $settings->max_login_attempts);

            if ($attempts >= $maxAttempts) {
                if ((bool) $settings->unlock_requires_admin) {
                    $payload['locked_by_admin'] = true;
                    $payload['locked_until'] = null;
                } else {
                    $payload['locked_by_admin'] = false;
                    $payload['locked_until'] = now()->addMinutes(max(1, (int) $settings->lockout_duration_minutes));
                }
            }

            $state->forceFill($payload)->save();

            return $state->refresh();
        });

        $attempts = (int) $state->failed_login_attempts;
        $context = [
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'user_id' => $user->id,
            'ip' => $ip,
            'failed_login_attempts' => $attempts,
        ];

        if ((bool) $settings->notify_user_on_failed_login) {
            $this->logPersistenceService->logSystemWarning(
                message: 'Falha de autenticação registrada para usuário do tenant.',
                category: 'tenant-security',
                operation: 'tenant_security.failed_authentication',
                userId: $user->id,
                context: $context,
                httpStatus: 401,
                processingStatus: 'denied',
            );
        }

        if ($attempts >= max(1, (int) $settings->max_login_attempts) && (bool) $settings->notify_admin_on_lockout) {
            $this->logPersistenceService->logSystemWarning(
                message: 'Usuário bloqueado pela política de segurança do tenant.',
                category: 'tenant-security',
                operation: 'tenant_security.user_lockout',
                userId: $user->id,
                context: array_merge($context, [
                    'unlock_requires_admin' => (bool) $settings->unlock_requires_admin,
                    'locked_until' => $state->locked_until?->toIso8601String(),
                    'locked_by_admin' => (bool) $state->locked_by_admin,
                ]),
                httpStatus: 423,
                processingStatus: 'denied',
            );
        }
    }

    public function isLocked(User $user, Tenant $tenant): bool
    {
        $state = $this->stateFor($user, $tenant);

        if (! $state instanceof TenantUserSecurityState) {
            return false;
        }

        if ((bool) $state->locked_by_admin) {
            return true;
        }

        return $state->locked_until instanceof Carbon && $state->locked_until->isFuture();
    }

    public function isLockedByAdmin(User $user, Tenant $tenant): bool
    {
        return (bool) $this->stateFor($user, $tenant)?->locked_by_admin;
    }

    private function stateFor(User $user, Tenant $tenant): ?TenantUserSecurityState
    {
        return TenantUserSecurityState::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();
    }

    private function stateForUpdate(User $user, Tenant $tenant): TenantUserSecurityState
    {
        DB::table('tenant_user_security_states')->insertOrIgnore([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantUserSecurityState::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
