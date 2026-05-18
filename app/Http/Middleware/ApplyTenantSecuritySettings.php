<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\TenantSecuritySetting;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Services\TenantSettings\TenantSecurityPolicyService;
use App\Services\TenantSettings\TenantSecurityRuntimeSettings;
use App\Support\Http\ApiResponse;
use App\Support\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApplyTenantSecuritySettings
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantSecurityRuntimeSettings $runtimeSettings,
        private TenantSecurityPolicyService $policyService,
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $settings = $this->runtimeSettings->settings();
        $user = $request->user();

        if (! $user instanceof User) {
            return ApiResponse::error('Usuário não autenticado.', status: 401);
        }

        $allowedIpRanges = $this->allowedIpRanges($settings->allowed_ip_ranges);

        if (! $this->ipIsAllowed($request->ip(), $allowedIpRanges)) {
            $this->logSecurityDenial('tenant_security.ip_denied', $user, $request, ['allowed_ip_ranges' => $settings->allowed_ip_ranges]);

            return ApiResponse::error('IP não autorizado para este tenant.', status: 403);
        }

        if ($this->policyService->isLocked($user)) {
            return ApiResponse::error(
                message: (bool) $user->locked_by_admin
                    ? 'Usuário bloqueado. Solicite desbloqueio ao administrador.'
                    : 'Usuário temporariamente bloqueado por falhas de autenticação.',
                status: 423,
            );
        }

        $token = $this->currentToken($user);

        if ($token instanceof Token) {
            if ($this->isExpiredByLifetime($token, $settings)) {
                return ApiResponse::error('Sessão expirada.', status: 401);
            }

            if ((bool) $settings->logout_on_password_change && $this->tokenPredatesPasswordChange($token, $user)) {
                return ApiResponse::error('Sessão encerrada por alteração de senha.', status: 401);
            }

            if ((bool) $settings->force_single_session_per_user) {
                $this->revokeOtherTokens($user, $token);
            }
        }

        if ($this->isExpiredByIdleTimeout($user, $token, $settings)) {
            return ApiResponse::error('Sessão expirada por inatividade.', status: 401);
        }

        return $next($request);
    }

    /**
     * @param  list<string>|null  $allowedRanges
     */
    private function ipIsAllowed(?string $ip, ?array $allowedRanges): bool
    {
        if ($allowedRanges === null || $allowedRanges === [] || $ip === null) {
            return true;
        }

        foreach ($allowedRanges as $range) {
            if ($this->ipMatchesRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    private function allowedIpRanges(mixed $ranges): ?array
    {
        if (! is_array($ranges)) {
            return null;
        }

        return array_values(array_filter($ranges, static fn (mixed $range): bool => is_string($range) && $range !== ''));
    }

    private function ipMatchesRange(string $ip, string $range): bool
    {
        if ($range === $ip) {
            return true;
        }

        if (! str_contains($range, '/')) {
            return false;
        }

        [$subnet, $prefix] = explode('/', $range, 2);

        if (! ctype_digit($prefix) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $prefixLength = (int) $prefix;

        if ($prefixLength < 0 || $prefixLength > 32) {
            return false;
        }

        $mask = -1 << (32 - $prefixLength);

        return ((int) ip2long($ip) & $mask) === ((int) ip2long($subnet) & $mask);
    }

    private function currentToken(User $user): ?Token
    {
        $token = $user->token();

        return $token instanceof Token ? $token : null;
    }

    private function isExpiredByLifetime(Token $token, TenantSecuritySetting $settings): bool
    {
        if ($token->created_at === null) {
            return false;
        }

        return $token->created_at->copy()->addMinutes(max(1, (int) $settings->session_lifetime_minutes))->isPast();
    }

    private function tokenPredatesPasswordChange(Token $token, User $user): bool
    {
        if ($token->created_at === null || $user->password_changed_at === null) {
            return false;
        }

        return $token->created_at->lt($user->password_changed_at);
    }

    private function revokeOtherTokens(User $user, Token $token): void
    {
        DB::table('oauth_access_tokens')
            ->where('user_id', (string) $user->getKey())
            ->where('id', '!=', $token->id)
            ->where('revoked', false)
            ->update(['revoked' => true]);
    }

    private function isExpiredByIdleTimeout(User $user, ?Token $token, TenantSecuritySetting $settings): bool
    {
        if ($settings->idle_timeout_minutes === null) {
            return false;
        }

        $tokenIdentifier = $token instanceof Token ? (string) $token->id : 'transient';

        $key = sprintf(
            'tenant_security:last_activity:tenant:%d:user:%d:token:%s',
            $this->tenantContext->require()->id,
            $user->id,
            $tokenIdentifier
        );
        $lastActivity = Cache::get($key);
        $now = now();
        Cache::put($key, $now->timestamp, now()->addMinutes(max(1, (int) $settings->idle_timeout_minutes) + 1));

        return is_int($lastActivity)
            && Carbon::createFromTimestamp($lastActivity)->addMinutes((int) $settings->idle_timeout_minutes)->isPast();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logSecurityDenial(string $operation, User $user, Request $request, array $context = []): void
    {
        $this->logPersistenceService->logSystemWarning(
            message: 'Requisição negada pela política de segurança do tenant.',
            category: 'tenant-security',
            operation: $operation,
            userId: $user->id,
            context: array_merge($context, ['ip' => $request->ip()]),
            httpStatus: 403,
            processingStatus: 'denied',
        );
    }
}
