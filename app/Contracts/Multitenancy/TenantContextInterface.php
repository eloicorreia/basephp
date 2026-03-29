<?php

declare(strict_types=1);

namespace App\Contracts\Multitenancy;

use App\Models\Tenant;

interface TenantContextInterface
{
    public function set(Tenant $tenant): void;

    public function get(): ?Tenant;

    public function require(): Tenant;

    public function hasTenant(): bool;

    public function clear(): void;
}