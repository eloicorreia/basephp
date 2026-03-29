<?php

declare(strict_types=1);

namespace App\Contracts\Multitenancy;

interface TenantAwareInterface
{
    public function tenantId(): string|int;
}