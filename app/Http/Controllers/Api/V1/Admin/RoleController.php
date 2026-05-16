<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\Admin\RoleService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleService->paginateForAdmin(
            activeOnly: $request->boolean('active_only', true),
            perPage: $request->integer('per_page', 15),
            sort: $request->string('sort', 'name')->toString(),
            direction: $request->string('direction', 'asc')->toString(),
        );

        return ApiResponse::paginated(
            paginator: $roles,
            data: RoleResource::collection($roles->items()),
        );
    }

    public function show(Role $role): JsonResponse
    {
        $role = $this->roleService->loadDetails($role);

        return ApiResponse::retrieved(new RoleResource($role));
    }
}
