<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\TenantPasswordPolicy;
use App\Models\User;
use App\Services\Admin\Web\AdminWebAuditService;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\IpRangeMatcher;
use App\Services\TenantSettings\TenantPasswordPolicyService;
use App\Services\TenantSettings\TenantSecurityPolicyService;
use App\Services\TenantSettings\TenantSecurityRuntimeSettings;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final readonly class WebAdminLoginService
{
    public function __construct(
        private TenantExecutionManager $tenantExecutionManager,
        private TenantPasswordPolicyService $passwordPolicyService,
        private TenantSecurityRuntimeSettings $securityRuntimeSettings,
        private TenantSecurityPolicyService $securityPolicyService,
        private TenantWebSessionService $webSessionService,
        private IpRangeMatcher $ipRangeMatcher,
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function attempt(Request $request, AdminWebAuditService $adminWebAuditService): WebAdminLoginResult
    {
        $email = (string) $request->input('email');
        $candidateUser = User::query()->where('email', $email)->first();
        $tenant = $this->tenantFromRequest($request);

        if (! $tenant instanceof Tenant) {
            $adminWebAuditService->loginFailed($request, $candidateUser, $email);

            if (! $this->hasTenantIdentifier($request)) {
                $this->logMissingTenant($request, $candidateUser);

                return WebAdminLoginResult::failure('Tenant obrigatório.');
            }

            return WebAdminLoginResult::failure('Tenant inválido ou inativo.');
        }

        $preflightFailure = $this->tenantPreflight($request, $tenant, $candidateUser, $adminWebAuditService, $email);

        if ($preflightFailure instanceof WebAdminLoginResult) {
            return $preflightFailure;
        }

        if (! Auth::guard('web')->attempt($request->only('email', 'password'), false)) {
            $adminWebAuditService->loginFailed($request, $candidateUser, $email);

            if ($candidateUser instanceof User) {
                $this->tenantExecutionManager->run($tenant, function () use ($candidateUser, $tenant, $request): void {
                    $this->securityPolicyService->registerFailedAuthentication(
                        user: $candidateUser,
                        tenant: $tenant,
                        settings: $this->securityRuntimeSettings->settings(),
                        ip: $request->ip(),
                    );
                });
            }

            return WebAdminLoginResult::failure('Credenciais inválidas.');
        }

        $request->session()->regenerate();

        $user = Auth::guard('web')->user();

        if (! $user instanceof User || ! WebAdminPermissions::allows($user, WebAdminPermissions::ACCESS)) {
            $adminWebAuditService->permissionDenied(
                request: $request,
                user: $user instanceof User ? $user : null,
                permission: WebAdminPermissions::ACCESS,
                reason: 'login_without_web_permission',
            );

            $this->logoutCurrentWebSession($request);

            return WebAdminLoginResult::failure('Usuário sem permissão para acessar o módulo administrativo.');
        }

        $mustChangePassword = $this->tenantExecutionManager->run(
            $tenant,
            fn (): bool => $this->afterSuccessfulTenantLogin($request, $tenant, $user)
        );
        $request->session()->put('admin_tenant_code', $tenant->code);
        $request->session()->put('admin_login_at', now()->timestamp);
        $request->session()->put('admin_password_changed_at', $user->password_changed_at?->timestamp);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $adminWebAuditService->loginSucceeded($request, $user);

        return WebAdminLoginResult::success($mustChangePassword || (bool) $user->must_change_password);
    }

    private function tenantPreflight(Request $request, Tenant $tenant, ?User $user, AdminWebAuditService $auditService, string $email): ?WebAdminLoginResult
    {
        return $this->tenantExecutionManager->run($tenant, function () use ($request, $tenant, $user, $auditService, $email): ?WebAdminLoginResult {
            $settings = $this->securityRuntimeSettings->settings();

            if (! $this->ipRangeMatcher->ipIsAllowed($request->ip(), $this->ipRangeMatcher->allowedIpRanges($settings->allowed_ip_ranges))) {
                $this->logSecurityWarning('tenant_security.web_login_ip_denied', $tenant, $user, $request, 403);
                $auditService->loginFailed($request, $user, $email);

                return WebAdminLoginResult::failure('IP não autorizado para este tenant.');
            }

            if (! $user instanceof User) {
                return null;
            }

            if (! $user->is_active) {
                $auditService->loginFailed($request, $user, $email);

                return WebAdminLoginResult::failure('Usuário inativo.');
            }

            if (! $user->tenantUsers()->where('tenant_id', $tenant->id)->where('is_active', true)->exists()) {
                $auditService->loginFailed($request, $user, $email);

                return WebAdminLoginResult::failure('Usuário sem acesso ao tenant informado.');
            }

            if ($this->securityPolicyService->isLocked($user, $tenant)) {
                $this->logSecurityWarning('tenant_security.web_login_locked', $tenant, $user, $request, 423);
                $auditService->loginFailed($request, $user, $email);

                return WebAdminLoginResult::failure('Usuário bloqueado pela política de segurança do tenant.');
            }

            $policy = $this->passwordPolicyService->getOrCreateDefault($user->id);

            if ($this->temporaryPasswordExpired($user, $policy)) {
                $this->logSecurityWarning('tenant_security.web_login_temporary_password_expired', $tenant, $user, $request, 403);
                $auditService->loginFailed($request, $user, $email);

                return WebAdminLoginResult::failure('Senha temporária expirada. Solicite uma nova senha.');
            }

            return null;
        });
    }

    private function afterSuccessfulTenantLogin(Request $request, Tenant $tenant, User $user): bool
    {
        $settings = $this->securityRuntimeSettings->settings();
        $policy = $this->passwordPolicyService->getOrCreateDefault($user->id);

        $this->securityPolicyService->registerSuccessfulAuthentication($user, $tenant, $request->ip());

        if (! (bool) $policy->active) {
            return (bool) $user->must_change_password;
        }

        $mustChangePassword = (bool) $user->must_change_password;

        if ((bool) $policy->must_change_password_on_first_login && $user->password_changed_at === null) {
            $mustChangePassword = true;
        }

        if ($this->passwordExpired($user, $policy)) {
            $mustChangePassword = true;
        }

        if ($mustChangePassword !== (bool) $user->must_change_password) {
            $user->forceFill(['must_change_password' => true])->save();
        }

        if ((bool) $settings->force_single_session_per_user) {
            $request->session()->migrate(true);
        }

        $webSession = $this->webSessionService->start($tenant, $user, $request, $settings);
        $request->session()->put('admin_web_session_id', $webSession->session_id);

        return $mustChangePassword;
    }

    private function tenantFromRequest(Request $request): ?Tenant
    {
        $tenantCode = trim((string) ($request->input('tenant_code') ?: $request->header('X-Tenant-Id', '')));

        if ($tenantCode === '') {
            return null;
        }

        return Tenant::query()
            ->where('code', $tenantCode)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->first();
    }

    private function hasTenantIdentifier(Request $request): bool
    {
        return trim((string) ($request->input('tenant_code') ?: $request->header('X-Tenant-Id', ''))) !== '';
    }

    private function temporaryPasswordExpired(User $user, TenantPasswordPolicy $policy): bool
    {
        if (! (bool) $policy->active || ! $user->must_change_password || $user->password_changed_at !== null) {
            return false;
        }

        return $user->created_at !== null
            && $user->created_at->copy()->addMinutes(max(1, (int) $policy->temporary_password_expiration_minutes))->isPast();
    }

    private function passwordExpired(User $user, TenantPasswordPolicy $policy): bool
    {
        if (! (bool) $policy->active || $policy->password_expiration_days === null) {
            return false;
        }

        if ($user->password_changed_at === null) {
            return true;
        }

        return $user->password_changed_at->copy()->addDays((int) $policy->password_expiration_days)->isPast();
    }

    private function logoutCurrentWebSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function logSecurityWarning(string $operation, Tenant $tenant, ?User $user, Request $request, int $status): void
    {
        $this->logPersistenceService->logSystemWarning(
            message: 'Login administrativo negado pela política de segurança do tenant.',
            category: 'tenant-security',
            operation: $operation,
            userId: $user?->id,
            context: [
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
                'user_id' => $user?->id,
                'ip' => $request->ip(),
            ],
            httpStatus: $status,
            processingStatus: 'denied',
        );
    }

    private function logMissingTenant(Request $request, ?User $user): void
    {
        $this->logPersistenceService->logSystemWarning(
            message: 'Login administrativo negado sem tenant resolvido.',
            category: 'tenant-security',
            operation: 'tenant_security.web_login_tenant_required',
            userId: $user?->id,
            context: [
                'tenant_id' => null,
                'tenant_code' => null,
                'user_id' => $user?->id,
                'ip' => $request->ip(),
            ],
            httpStatus: 422,
            processingStatus: 'denied',
        );
    }
}
