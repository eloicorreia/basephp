<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTO\Admin\CreatePermissionDTO;
use App\DTO\Admin\UpdatePermissionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StorePermissionRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Services\Admin\PermissionService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $permissions = $this->permissionService->paginateForAdmin(
            activeOnly: $request->boolean('active_only', true),
            context: $request->filled('context') ? $request->string('context')->toString() : null,
            perPage: $request->integer('per_page', 50),
        );

        return ApiResponse::paginated(
            paginator: $permissions,
            data: PermissionResource::collection($permissions->items()),
        );
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissionService->create(
            CreatePermissionDTO::fromArray($request->validated())
        );

        return ApiResponse::success(
            data: new PermissionResource($permission),
            message: 'Permissão criada com sucesso.',
            status: 201,
        );
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission = $this->permissionService->update(
            $permission,
            UpdatePermissionDTO::fromArray($request->validated())
        );

        return ApiResponse::success(
            data: new PermissionResource($permission),
            message: 'Permissão atualizada com sucesso.',
        );
    }

    public function enable(Permission $permission): JsonResponse
    {
        return ApiResponse::success(
            data: new PermissionResource($this->permissionService->enable($permission)),
            message: 'Permissão habilitada com sucesso.',
        );
    }

    public function disable(Permission $permission): JsonResponse
    {
        return ApiResponse::success(
            data: new PermissionResource($this->permissionService->disable($permission)),
            message: 'Permissão desabilitada com sucesso.',
        );
    }
}
