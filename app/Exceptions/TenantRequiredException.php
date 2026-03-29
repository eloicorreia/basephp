<?php

declare(strict_types=1);

namespace App\Exceptions;

class TenantRequiredException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Tenant não informado.',
            statusCode: 400,
            errors: [],
        );
    }
}