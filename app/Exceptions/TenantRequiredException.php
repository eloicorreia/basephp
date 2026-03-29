<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class TenantRequiredException extends ApiException
{
    public function __construct()
    {
        parent::__construct('Tenant não informado.', Response::HTTP_BAD_REQUEST);
    }
}