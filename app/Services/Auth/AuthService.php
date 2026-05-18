<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserPasswordHistory;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\TenantPasswordPolicyService;
use App\Services\TenantSettings\TenantPasswordValidatorService;
use App\Services\TenantSettings\TenantSecurityRuntimeSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly LogPersistenceService $logPersistenceService,
        private readonly TenantExecutionManager $tenantExecutionManager,
        private readonly TenantPasswordPolicyService $passwordPolicyService,
        private readonly TenantPasswordValidatorService $passwordValidatorService,
        private readonly TenantSecurityRuntimeSettings $securityRuntimeSettings,
    ) {}

    public function changePassword(User $user, string $currentPassword, string $newPassword, ?Tenant $tenant = null): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            $this->logPersistenceService->logSystemWarning(
                message: 'Tentativa de troca de senha com senha atual inválida.',
                category: 'auth',
                operation: 'change_password_invalid_current',
                userId: $user->id,
                httpStatus: 422,
                processingStatus: 'denied',
            );

            throw ValidationException::withMessages([
                'current_password' => ['A senha atual informada é inválida.'],
            ]);
        }

        if ($tenant instanceof Tenant) {
            $this->tenantExecutionManager->run(
                $tenant,
                fn (): null => $this->validateTenantPassword($user, $newPassword)
            );
        }

        $before = [
            'must_change_password' => $user->must_change_password,
            'password_changed_at' => $user->password_changed_at,
        ];
        $previousPasswordHash = (string) $user->password;

        DB::transaction(function () use ($user, $newPassword, $tenant, $previousPasswordHash): void {
            $user->forceFill([
                'password' => $newPassword,
                'must_change_password' => false,
                'password_changed_at' => now(),
            ]);

            $user->save();

            if ($tenant instanceof Tenant) {
                $this->tenantExecutionManager->run(
                    $tenant,
                    fn (): null => $this->recordPasswordHistory($user, $previousPasswordHash)
                );

                if ((bool) $this->tenantExecutionManager->run(
                    $tenant,
                    fn (): bool => (bool) $this->securityRuntimeSettings->settings()->logout_on_password_change
                )) {
                    $this->revokeUserTokens($user);
                }
            }
        });

        $this->logPersistenceService->logAudit(
            action: 'user.password.changed',
            auditableType: User::class,
            auditableId: $user->id,
            beforeData: $before,
            afterData: [
                'must_change_password' => false,
                'password_changed_at' => $user->password_changed_at,
            ],
            userId: $user->id,
            userRole: $user->role?->code,
        );
    }

    public function changePasswordForRequest(User $user, string $currentPassword, string $newPassword, Request $request): void
    {
        $this->changePassword(
            user: $user,
            currentPassword: $currentPassword,
            newPassword: $newPassword,
            tenant: $this->tenantFromRequest($request),
        );
    }

    private function validateTenantPassword(User $user, string $newPassword): null
    {
        $policy = $this->passwordPolicyService->getOrCreateDefault($user->id);

        if (! (bool) $policy->active) {
            return null;
        }

        $errors = $this->passwordValidatorService->validate($newPassword, $policy, $user);

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'new_password' => $errors,
            ]);
        }

        return null;
    }

    private function recordPasswordHistory(User $user, string $previousPasswordHash): null
    {
        $policy = $this->passwordPolicyService->getOrCreateDefault($user->id);

        if (! (bool) $policy->active || (int) $policy->password_history_count <= 0) {
            return null;
        }

        UserPasswordHistory::query()->create([
            'user_id' => $user->id,
            'password_hash' => $previousPasswordHash,
        ]);

        return null;
    }

    private function revokeUserTokens(User $user): void
    {
        DB::table('oauth_access_tokens')
            ->where('user_id', (string) $user->getKey())
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }

    private function tenantFromRequest(Request $request): ?Tenant
    {
        $tenantCode = trim((string) $request->header('X-Tenant-Id', ''));

        if ($tenantCode === '' && $request->hasSession()) {
            $tenantCode = trim((string) $request->session()->get('admin_tenant_code', ''));
        }

        if ($tenantCode === '') {
            return null;
        }

        return Tenant::query()
            ->where('code', $tenantCode)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->first();
    }
}
