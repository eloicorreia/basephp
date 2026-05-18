<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\TenantSecuritySetting;
use App\Models\TenantUserWebSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class TenantWebSessionService
{
    public function start(Tenant $tenant, User $user, Request $request, TenantSecuritySetting $settings): TenantUserWebSession
    {
        return DB::transaction(function () use ($tenant, $user, $request, $settings): TenantUserWebSession {
            if ((bool) $settings->force_single_session_per_user) {
                $this->revokeOtherSessions($tenant, $user, $request->session()->getId(), 'single_session_enforced');
            }

            return TenantUserWebSession::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                    'session_id' => $request->session()->getId(),
                ],
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 65535),
                    'last_activity_at' => now(),
                    'revoked_at' => null,
                    'revoked_reason' => null,
                ]
            );
        });
    }

    public function activeSession(Tenant $tenant, User $user, string $sessionId): ?TenantUserWebSession
    {
        return TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->first();
    }

    public function touch(TenantUserWebSession $session, ?string $ip = null): void
    {
        $session->forceFill([
            'ip_address' => $ip,
            'last_activity_at' => now(),
        ])->save();
    }

    public function revokeCurrent(Tenant $tenant, User $user, string $sessionId, string $reason): void
    {
        TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function revokeOtherSessions(Tenant $tenant, User $user, string $currentSessionId, string $reason): void
    {
        TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('session_id', '!=', $currentSessionId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }

    public function revokeAllForUser(Tenant $tenant, User $user, string $reason): void
    {
        TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => $reason,
                'updated_at' => now(),
            ]);
    }
}
