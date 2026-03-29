<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class TenantNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Tenant não encontrado.', Response::HTTP_NOT_FOUND);
    }
}