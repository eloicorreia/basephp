<?php

declare(strict_types=1);

namespace App\Contracts\Multitenancy;

use App\Models\Tenant;

interface TenantResolverInterface
{
    public function resolveById(string|int $tenantId): Tenant;

    public function resolveFromHttpRequest(): Tenant;
}