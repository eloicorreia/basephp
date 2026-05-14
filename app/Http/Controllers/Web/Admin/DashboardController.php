<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Models\SystemLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                'users' => User::query()->count(),
                'tenants' => Tenant::query()->count(),
                'api_request_logs' => ApiRequestLog::query()->count(),
                'system_logs' => SystemLog::query()->count(),
            ],
        ]);
    }
}
