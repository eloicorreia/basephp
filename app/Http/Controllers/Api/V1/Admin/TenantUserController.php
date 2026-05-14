<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTO\Admin\CreateTenantUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreTenantUserRequest;
use App\Http\Resources\Api\V1\TenantUserResource;
use App\Models\TenantUser;
use App\Services\Admin\TenantUserService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantUserController extends Controller
{
    public function __construct(
        private readonly TenantUserService $tenantUserService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = TenantUser::query()
            ->with(['tenant', 'user', 'role'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return ApiResponse::paginated(
            paginator: $items,
            data: TenantUserResource::collection($items->items()),
        );
    }

    public function store(StoreTenantUserRequest $request): JsonResponse
    {
        $dto = CreateTenantUserDTO::fromArray($request->validated());

        $tenantUser = $this->tenantUserService
            ->createOrUpdate($dto)
            ->load(['tenant', 'user', 'role']);

        return ApiResponse::success(
            data: new TenantUserResource($tenantUser),
            message: 'Vínculo salvo com sucesso.',
            status: 201,
        );
    }
}
