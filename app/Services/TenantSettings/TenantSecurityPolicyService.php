<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Support\Carbon;

final readonly class TenantSecurityPolicyService
{
    public function __construct(
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function registerSuccessfulAuthentication(User $user): void
    {
        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'locked_by_admin' => false,
        ])->save();
    }

    public function registerFailedAuthentication(User $user, TenantSecuritySetting $settings, ?string $ip = null): void
    {
        $attempts = (int) $user->failed_login_attempts + 1;
        $payload = ['failed_login_attempts' => $attempts];
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

        $user->forceFill($payload)->save();

        if ((bool) $settings->notify_user_on_failed_login) {
            $this->logPersistenceService->logSystemWarning(
                message: 'Falha de autenticação registrada para usuário do tenant.',
                category: 'tenant-security',
                operation: 'tenant_security.failed_authentication',
                userId: $user->id,
                context: ['ip' => $ip, 'failed_login_attempts' => $attempts],
                httpStatus: 401,
                processingStatus: 'denied',
            );
        }

        if ($attempts >= $maxAttempts && (bool) $settings->notify_admin_on_lockout) {
            $this->logPersistenceService->logSystemWarning(
                message: 'Usuário bloqueado pela política de segurança do tenant.',
                category: 'tenant-security',
                operation: 'tenant_security.user_lockout',
                userId: $user->id,
                context: [
                    'ip' => $ip,
                    'unlock_requires_admin' => (bool) $settings->unlock_requires_admin,
                    'locked_until' => $user->locked_until?->toIso8601String(),
                ],
                httpStatus: 423,
                processingStatus: 'denied',
            );
        }
    }

    public function isLocked(User $user): bool
    {
        if ((bool) $user->locked_by_admin) {
            return true;
        }

        return $user->locked_until instanceof Carbon && $user->locked_until->isFuture();
    }
}
