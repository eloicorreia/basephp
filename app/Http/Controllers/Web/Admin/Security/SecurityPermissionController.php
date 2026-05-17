<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin\Security;

use App\DTO\Admin\CreatePermissionDTO;
use App\DTO\Admin\UpdatePermissionDTO;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\Security\StoreSecurityPermissionRequest;
use App\Http\Requests\Web\Admin\Security\UpdateSecurityPermissionRequest;
use App\Models\Permission;
use App\Services\Admin\PermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SecurityPermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.security.permissions.index', [
            'permissions' => $this->permissionService->paginateForAdmin(
                activeOnly: $request->boolean('active_only', false),
                context: $request->filled('context') ? $request->string('context')->toString() : null,
                perPage: $request->integer('per_page', 50),
            ),
            'filters' => [
                'active_only' => $request->boolean('active_only', false),
                'context' => $request->string('context')->toString(),
                'per_page' => $request->integer('per_page', 50),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.security.permissions.create');
    }

    public function store(StoreSecurityPermissionRequest $request): RedirectResponse
    {
        $permission = $this->permissionService->create(CreatePermissionDTO::fromArray($request->validated()));

        return redirect()
            ->route('admin.security.permissions.edit', $permission)
            ->with('status', 'Permissão criada com sucesso.');
    }

    public function edit(Permission $permission): View
    {
        return view('admin.security.permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdateSecurityPermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->permissionService->update($permission, UpdatePermissionDTO::fromArray($request->validated()));

        return redirect()
            ->route('admin.security.permissions.edit', $permission)
            ->with('status', 'Permissão atualizada com sucesso.');
    }

    public function enable(Permission $permission): RedirectResponse
    {
        try {
            $this->permissionService->enable($permission);
        } catch (ApiException $exception) {
            return back()->withErrors(['permission' => $exception->getMessage()]);
        }

        return back()->with('status', 'Permissão habilitada com sucesso.');
    }

    public function disable(Permission $permission): RedirectResponse
    {
        try {
            $this->permissionService->disable($permission);
        } catch (ApiException $exception) {
            return back()->withErrors(['permission' => $exception->getMessage()]);
        }

        return back()->with('status', 'Permissão desabilitada com sucesso.');
    }
}
