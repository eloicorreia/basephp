<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Http\Request;

final readonly class WebAdminLogoutService
{
    public function __construct(
        private TenantWebSessionService $webSessionService,
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function finalize(Request $request, ?User $user): void
    {
        $tenant = $this->tenantFromSession($request);

        if (! $tenant instanceof Tenant || ! $user instanceof User) {
            return;
        }

        $sessionId = $this->sessionId($request);

        $this->webSessionService->revokeCurrent($tenant, $user, $sessionId, 'logout');

        $this->logPersistenceService->logSystemInfo(
            message: 'Logout administrativo tenant-aware finalizado.',
            category: 'tenant-security',
            operation: 'tenant_security.web_logout',
            userId: $user->id,
            context: [
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'session_id' => $sessionId,
            ],
            httpStatus: 302,
            processingStatus: 'success',
        );
    }

    private function sessionId(Request $request): string
    {
        $sessionId = $request->session()->get('admin_web_session_id');

        return is_string($sessionId) && $sessionId !== ''
            ? $sessionId
            : $request->session()->getId();
    }

    private function tenantFromSession(Request $request): ?Tenant
    {
        $tenantCode = trim((string) $request->session()->get('admin_tenant_code', ''));

        if ($tenantCode === '') {
            return null;
        }

        return Tenant::query()
            ->where('code', $tenantCode)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->first();
    }
}
