<?php

declare(strict_types=1);

namespace App\Exceptions\Tenant;

use RuntimeException;

final class TenantNotAvailableForAsyncExecutionException extends RuntimeException
{
    public static function forTenantId(int $tenantId): self
    {
        return new self(
            sprintf(
                'O tenant com id [%d] não está disponível para execução assíncrona.',
                $tenantId
            )
        );
    }
}