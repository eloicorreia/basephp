<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Contracts\Multitenancy\TenantContextInterface;
use App\Exceptions\Tenant\TenantContextNotDefinedException;
use App\Models\Tenant;

final class TenantContext implements TenantContextInterface
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

    public function require(): Tenant
    {
        if ($this->tenant === null) {
            throw new TenantContextNotDefinedException(
                'Contexto de tenant não foi definido para a execução atual.'
            );
        }

        return $this->tenant;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}