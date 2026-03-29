<?php

declare(strict_types=1);

namespace App\Exceptions;

final class TenantContextNotDefinedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Contexto de tenant não foi definido para a execução atual.',
            statusCode: 500,
            errors: [],
        );
    }
}