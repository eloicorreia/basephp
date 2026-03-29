<?php

declare(strict_types=1);

namespace App\Exceptions;

class BusinessException extends ApiException
{
    /**
     * @param array<int, array<string, mixed>> $errors
     */
    public function __construct(
        string $message,
        array $errors = []
    ) {
        parent::__construct(
            message: $message,
            statusCode: 422,
            errors: $errors,
        );
    }
}