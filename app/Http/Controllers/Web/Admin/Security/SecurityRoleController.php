<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Security;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Security\StoreSecurityRoleRequest;
use App\Http\Requests\Web\Admin\Security\UpdateSecurityRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Admin\Web\Security\SecurityRoleManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SecurityRoleController extends Controller
{
    public function __construct(
        private readonly SecurityRoleManagementService $securityRoleManagementService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.security.roles.index', [
            'roles' => $this->securityRoleManagementService->paginate(
                includeInactive: $request->boolean('include_inactive', true),
                perPage: $request->integer('per_page', 20),
            ),
            'filters' => [
                'include_inactive' => $request->boolean('include_inactive', true),
                'per_page' => $request->integer('per_page', 20),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.security.roles.create');
    }

    public function store(StoreSecurityRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = $this->securityRoleManagementService->create(
            code: (string) $data['code'],
            name: (string) $data['name'],
            active: $request->boolean('active', true),
        );

        return redirect()
            ->route('admin.security.roles.edit', $role)
            ->with('status', 'Role criada com sucesso.');
    }

    public function edit(Role $role): View
    {
        return view('admin.security.roles.edit', [
            'managedRole' => $role->load('permissions'),
            'permissionsByGroup' => Permission::query()
                ->where('active', true)
                ->orderBy('group')
                ->orderBy('code')
                ->get()
                ->groupBy('group'),
        ]);
    }

    public function update(UpdateSecurityRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        try {
            $role = $this->securityRoleManagementService->update(
                role: $role,
                name: (string) $data['name'],
                active: $request->boolean('active'),
            );
            $this->securityRoleManagementService->syncPermissions(
                role: $role,
                permissionIds: array_values(array_map('intval', $data['permission_ids'] ?? [])),
            );
        } catch (ApiException $exception) {
            return back()->withErrors(['role' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.security.roles.edit', $role)
            ->with('status', 'Role atualizada com sucesso.');
    }
}
