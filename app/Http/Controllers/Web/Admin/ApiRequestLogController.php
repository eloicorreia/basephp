<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Models\User;
use App\Services\Admin\Web\AdminLogQueryService;
use App\Services\Admin\Web\AdminWebAuditService;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ApiRequestLogController extends Controller
{
    public function index(Request $request, AdminLogQueryService $adminLogQueryService): View
    {
        $filters = $adminLogQueryService->normalizeApiRequestFilters(
            filters: $request->only(['date_from', 'date_to', 'method', 'status', 'search', 'per_page']),
            perPage: $request->integer('per_page', (int) config('admin_web.logs.per_page', 15)),
        );

        return view('admin.logs.api-requests.index', [
            'logs' => $adminLogQueryService->paginateApiRequestLogs(
                filters: $filters,
                perPage: (int) $filters['per_page'],
            ),
            'filters' => $filters,
            'maxPeriodDays' => (int) config('admin_web.logs.max_period_days', 31),
        ]);
    }

    public function show(
        Request $request,
        ApiRequestLog $apiRequestLog,
        AdminLogQueryService $adminLogQueryService,
        AdminWebAuditService $adminWebAuditService
    ): View {
        $user = $request->user('web');

        if ($user instanceof User) {
            $adminWebAuditService->logDetailViewed($request, $user, $apiRequestLog);
        }

        return view('admin.logs.api-requests.show', [
            'log' => $adminLogQueryService->apiRequestDetail($apiRequestLog),
            'canViewPayload' => $user instanceof User
                && WebAdminPermissions::allows($user, WebAdminPermissions::API_REQUEST_LOG_PAYLOADS_VIEW),
        ]);
    }

    public function payload(
        Request $request,
        ApiRequestLog $apiRequestLog,
        AdminLogQueryService $adminLogQueryService,
        AdminWebAuditService $adminWebAuditService
    ): View {
        $user = $request->user('web');

        if ($user instanceof User) {
            $adminWebAuditService->logPayloadViewed($request, $user, $apiRequestLog);
        }

        return view('admin.logs.api-requests.payload', [
            'log' => $adminLogQueryService->apiRequestPayload($apiRequestLog),
        ]);
    }
}
