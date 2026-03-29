<?php

declare(strict_types=1);

namespace App\Exceptions;

class TenantNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Tenant não encontrado.',
            statusCode: 404,
            errors: [],
        );
    }
}