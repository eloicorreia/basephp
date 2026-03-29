<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::query()
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => TenantResource::collection($tenants->items()),
            'meta' => [
                'page' => $tenants->currentPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
                'last_page' => $tenants->lastPage(),
            ],
        ]);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantProvisioningService->provision(
            code: $request->validated('code'),
            name: $request->validated('name'),
            schemaName: $request->validated('schema_name'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Tenant provisionado com sucesso.',
            'data' => new TenantResource($tenant),
        ], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dados recuperados com sucesso.',
            'data' => new TenantResource($tenant),
        ]);
    }
}