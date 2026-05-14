<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::query()
            ->orderBy('id', 'desc')
            ->paginate(15);

        return ApiResponse::paginated(
            paginator: $tenants,
            data: TenantResource::collection($tenants->items()),
        );
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantProvisioningService->provision(
            code: $request->validated('code'),
            name: $request->validated('name'),
            schemaName: $request->validated('schema_name'),
        );

        return ApiResponse::success(
            data: new TenantResource($tenant),
            message: 'Tenant provisionado com sucesso.',
            status: 201,
        );
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return ApiResponse::retrieved(new TenantResource($tenant));
    }
}
