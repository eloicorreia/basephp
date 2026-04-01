<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\TenantNotFoundException;
use App\Exceptions\TenantRequiredException;
use App\Models\Tenant;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantMiddleware
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantSearchPathService $tenantSearchPathService
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenantCode = $request->header('X-Tenant-Id');

        if ($tenantCode === null || trim($tenantCode) === '') {
            throw new TenantRequiredException();
        }

        $tenant = Tenant::query()
            ->where('code', trim($tenantCode))
            ->where('status', 'active')
            ->first();

        if ($tenant === null) {
            throw new TenantNotFoundException();
        }

        $this->tenantSearchPathService->setTenantSchema($tenant->schema_name);
        $this->tenantContext->set($tenant);

        try {
            return $next($request);
        } finally {
            $this->tenantContext->clear();
            $this->tenantSearchPathService->resetToPublic();
        }
    }
}