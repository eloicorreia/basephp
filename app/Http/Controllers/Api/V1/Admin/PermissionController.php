<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
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
}
