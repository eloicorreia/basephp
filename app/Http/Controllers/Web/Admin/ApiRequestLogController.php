<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Services\Admin\Web\AdminLogQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ApiRequestLogController extends Controller
{
    public function index(Request $request, AdminLogQueryService $adminLogQueryService): View
    {
        return view('admin.logs.api-requests.index', [
            'logs' => $adminLogQueryService->paginateApiRequestLogs(
                filters: $request->only(['method', 'status', 'search']),
                perPage: $request->integer('per_page', 15),
            ),
            'filters' => $request->only(['method', 'status', 'search']),
        ]);
    }

    public function show(ApiRequestLog $apiRequestLog, AdminLogQueryService $adminLogQueryService): View
    {
        return view('admin.logs.api-requests.show', [
            'log' => $adminLogQueryService->apiRequestDetail($apiRequestLog),
        ]);
    }
}
