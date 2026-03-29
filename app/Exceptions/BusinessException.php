<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class BusinessException extends ApiException
{
    public function __construct(
        string $message,
        array $errors = []
    ) {
        parent::__construct($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}