<?php

declare(strict_types=1);

namespace App\Services\Mail\Contracts;

use App\DTO\Mail\TenantMailConfigData;

interface TenantMailConnectionTesterInterface
{
    public function test(TenantMailConfigData $config): void;
}
