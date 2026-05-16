<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->withCount('users')
            ->when(
                $request->boolean('active_only', true),
                static fn ($query) => $query->where('active', true)
            )
            ->orderBy('name')
            ->paginate(15);

        return ApiResponse::paginated(
            paginator: $roles,
            data: RoleResource::collection($roles->items()),
        );
    }

    public function show(Role $role): JsonResponse
    {
        $role->loadCount('users');

        return ApiResponse::retrieved(new RoleResource($role));
    }
}
