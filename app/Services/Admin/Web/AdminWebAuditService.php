<?php

declare(strict_types=1);

namespace App\Services\Admin\Web;

use App\Models\ApiRequestLog;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Http\Request;

final readonly class AdminWebAuditService
{
    public const LOGIN_SUCCESS = 'web_admin_login_success';

    public const LOGIN_FAILED = 'web_admin_login_failed';

    public const LOGOUT = 'web_admin_logout';

    public const PERMISSION_DENIED = 'web_admin_permission_denied';

    public const LOG_DETAIL_VIEW = 'web_admin_log_detail_view';

    public const LOG_PAYLOAD_VIEW = 'web_admin_log_payload_view';

    private const AUDITABLE_TYPE = 'web_admin';

    public function __construct(
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function loginSucceeded(Request $request, User $user): void
    {
        $this->record(
            action: self::LOGIN_SUCCESS,
            auditableType: User::class,
            auditableId: $user->id,
            user: $user,
            afterData: [
                'guard' => 'web',
                'email' => $user->email,
                'route' => $request->path(),
            ],
        );
    }

    public function loginFailed(Request $request, ?User $user, string $email): void
    {
        $this->record(
            action: self::LOGIN_FAILED,
            auditableType: $user instanceof User ? User::class : self::AUDITABLE_TYPE,
            auditableId: $user?->id,
            user: $user,
            afterData: [
                'guard' => 'web',
                'email' => $email,
                'reason' => 'invalid_credentials',
                'route' => $request->path(),
            ],
        );
    }

    public function logout(Request $request, ?User $user): void
    {
        $this->record(
            action: self::LOGOUT,
            auditableType: $user instanceof User ? User::class : self::AUDITABLE_TYPE,
            auditableId: $user?->id,
            user: $user,
            afterData: [
                'guard' => 'web',
                'route' => $request->path(),
            ],
        );
    }

    public function permissionDenied(Request $request, ?User $user, string $permission, string $reason = 'missing_permission'): void
    {
        $this->record(
            action: self::PERMISSION_DENIED,
            auditableType: $user instanceof User ? User::class : self::AUDITABLE_TYPE,
            auditableId: $user?->id,
            user: $user,
            afterData: [
                'guard' => 'web',
                'permission' => $permission,
                'reason' => $reason,
                'route' => $request->path(),
            ],
        );
    }

    public function logDetailViewed(Request $request, User $user, ApiRequestLog $log): void
    {
        $this->recordLogView(
            action: self::LOG_DETAIL_VIEW,
            request: $request,
            user: $user,
            log: $log,
        );
    }

    public function logPayloadViewed(Request $request, User $user, ApiRequestLog $log): void
    {
        $this->recordLogView(
            action: self::LOG_PAYLOAD_VIEW,
            request: $request,
            user: $user,
            log: $log,
        );
    }

    private function recordLogView(string $action, Request $request, User $user, ApiRequestLog $log): void
    {
        $this->record(
            action: $action,
            auditableType: ApiRequestLog::class,
            auditableId: $log->id,
            user: $user,
            afterData: [
                'guard' => 'web',
                'api_request_log_id' => $log->id,
                'request_id' => $log->request_id,
                'trace_id' => $log->trace_id,
                'route' => $request->path(),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $afterData
     */
    private function record(
        string $action,
        string $auditableType,
        ?int $auditableId,
        ?User $user,
        array $afterData,
    ): void {
        $this->logPersistenceService->logAudit(
            action: $action,
            auditableType: $auditableType,
            auditableId: $auditableId,
            beforeData: null,
            afterData: $afterData,
            userId: $user?->id,
            userRole: $user?->role?->code,
        );
    }
}
