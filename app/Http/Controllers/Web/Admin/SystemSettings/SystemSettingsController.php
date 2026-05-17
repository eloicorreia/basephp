<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\SystemSettings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TenantSettings\TenantSelectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SystemSettingsController extends Controller
{
    public function __construct(
        private readonly TenantSelectionService $tenantSelectionService,
    ) {}

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user('web');
        $tenants = $this->tenantSelectionService->availableTenants($user);
        $tenant = $this->tenantSelectionService->resolveForUser(
            user: $user,
            tenantCode: $request->filled('tenant') ? $request->string('tenant')->toString() : null,
        );

        return view('admin.system-settings.index', [
            'tenants' => $tenants,
            'selectedTenant' => $tenant,
        ]);
    }
}
