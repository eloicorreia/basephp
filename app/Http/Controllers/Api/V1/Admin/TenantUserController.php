<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Admin\CreateTenantUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreTenantUserRequest;
use App\Http\Resources\TenantUserResource;
use App\Models\TenantUser;
use App\Services\Admin\TenantUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantUserController extends Controller
{
    public function __construct(
        private readonly TenantUserService $tenantUserService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $items = TenantUser::query()
            ->with(['tenant', 'user', 'role'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => TenantUserResource::collection($items->items()),
            'meta' => [
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function store(StoreTenantUserRequest $request): JsonResponse
    {
        $dto = CreateTenantUserDTO::fromArray($request->validated());

        $tenantUser = $this->tenantUserService
            ->createOrUpdate($dto)
            ->load(['tenant', 'user', 'role']);

        return response()->json([
            'success' => true,
            'message' => 'Vínculo salvo com sucesso.',
            'data' => new TenantUserResource($tenantUser),
        ], 201);
    }
}