<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Services\Auth\TenantWebSessionService;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\TenantSettings\IpRangeMatcher;
use App\Services\TenantSettings\TenantSecurityPolicyService;
use App\Services\TenantSettings\TenantSecurityRuntimeSettings;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApplyWebTenantSecuritySettings
{
    public function __construct(
        private TenantExecutionManager $tenantExecutionManager,
        private TenantSecurityRuntimeSettings $runtimeSettings,
        private TenantSecurityPolicyService $policyService,
        private TenantWebSessionService $webSessionService,
        private IpRangeMatcher $ipRangeMatcher,
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $user instanceof User) {
            abort(401, 'Não autenticado.');
        }

        $tenantCode = trim((string) $request->session()->get('admin_tenant_code', ''));

        if ($tenantCode === '') {
            return $next($request);
        }

        $tenant = Tenant::query()
            ->where('code', $tenantCode)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->first();

        if (! $tenant instanceof Tenant) {
            return $this->deny($request, null, $user, 'tenant_security.web_session_tenant_invalid', 'Tenant da sessão inválido.');
        }

        return $this->tenantExecutionManager->run($tenant, function () use ($request, $next, $tenant, $user): Response {
            $settings = $this->runtimeSettings->settings();

            if (! $user->tenantUsers()->where('tenant_id', $tenant->id)->where('is_active', true)->exists()) {
                return $this->deny($request, $tenant, $user, 'tenant_security.web_session_membership_denied', 'Usuário sem acesso ao tenant da sessão.');
            }

            if (! $this->ipRangeMatcher->ipIsAllowed($request->ip(), $this->ipRangeMatcher->allowedIpRanges($settings->allowed_ip_ranges))) {
                return $this->deny($request, $tenant, $user, 'tenant_security.web_session_ip_denied', 'IP não autorizado para este tenant.');
            }

            if ($this->policyService->isLocked($user, $tenant)) {
                return $this->deny($request, $tenant, $user, 'tenant_security.web_session_user_locked', 'Usuário bloqueado pela política de segurança do tenant.');
            }

            $sessionId = $this->sessionId($request);
            $webSession = $this->webSessionService->activeSession($tenant, $user, $sessionId);

            if ($webSession === null) {
                return $this->deny($request, $tenant, $user, 'tenant_security.web_session_revoked', 'Sessão encerrada.');
            }

            if ($this->expiredByLifetime($request, $settings)) {
                $this->webSessionService->revokeCurrent($tenant, $user, $sessionId, 'session_lifetime_expired');

                return $this->deny($request, $tenant, $user, 'tenant_security.web_session_lifetime_expired', 'Sessão expirada.');
            }

            if ($this->expiredByIdleTimeout($webSession->last_activity_at, $settings)) {
                $this->webSessionService->revokeCurrent($tenant, $user, $sessionId, 'idle_timeout_expired');

                return $this->deny($request, $tenant, $user, 'tenant_security.web_session_idle_expired', 'Sessão expirada por inatividade.');
            }

            if ((bool) $settings->logout_on_password_change && $this->sessionPredatesPasswordChange($request, $user)) {
                $this->webSessionService->revokeCurrent($tenant, $user, $sessionId, 'password_changed');

                return $this->deny($request, $tenant, $user, 'tenant_security.web_session_password_changed', 'Sessão encerrada por alteração de senha.');
            }

            if ((bool) $settings->force_single_session_per_user) {
                $this->webSessionService->revokeOtherSessions($tenant, $user, $sessionId, 'single_session_enforced');
            }

            $this->webSessionService->touch($webSession, $request->ip());

            return $next($request);
        });
    }

    private function expiredByLifetime(Request $request, TenantSecuritySetting $settings): bool
    {
        $loginAt = $request->session()->get('admin_login_at');

        return is_int($loginAt)
            && now()->getTimestamp() - $loginAt > max(1, (int) $settings->session_lifetime_minutes) * 60;
    }

    private function expiredByIdleTimeout(mixed $lastActivityAt, TenantSecuritySetting $settings): bool
    {
        if ($settings->idle_timeout_minutes === null || ! $lastActivityAt instanceof Carbon) {
            return false;
        }

        return $lastActivityAt->copy()->addMinutes((int) $settings->idle_timeout_minutes)->isPast();
    }

    private function sessionPredatesPasswordChange(Request $request, User $user): bool
    {
        if ($user->password_changed_at === null) {
            return false;
        }

        $sessionPasswordChangedAt = $request->session()->get('admin_password_changed_at');

        return is_int($sessionPasswordChangedAt) && $sessionPasswordChangedAt < $user->password_changed_at->timestamp;
    }

    private function deny(Request $request, ?Tenant $tenant, User $user, string $operation, string $message): RedirectResponse
    {
        $this->logPersistenceService->logSystemWarning(
            message: 'Sessão administrativa negada pela política de segurança do tenant.',
            category: 'tenant-security',
            operation: $operation,
            userId: $user->id,
            context: [
                'tenant_id' => $tenant?->id,
                'tenant_code' => $tenant?->code,
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'session_id' => $request->session()->getId(),
            ],
            httpStatus: 403,
            processingStatus: 'denied',
        );

        Auth::guard('web')->logout();
        $request->session()->forget(['admin_tenant_code', 'admin_login_at', 'admin_password_changed_at', 'admin_web_session_id']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    private function sessionId(Request $request): string
    {
        $sessionId = $request->session()->get('admin_web_session_id');

        return is_string($sessionId) && $sessionId !== ''
            ? $sessionId
            : $request->session()->getId();
    }
}
