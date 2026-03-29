<?php

declare(strict_types=1);

namespace App\Support\Tenant;

use App\Models\Tenant;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}