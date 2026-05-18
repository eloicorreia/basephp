<?php

declare(strict_types=1);

namespace App\Exceptions;

final class TenantConflictException extends ApiException
{
    public static function codeOrSchemaAlreadyExists(): self
    {
        return new self(
            message: 'Já existe tenant usando o código ou schema informado.',
            statusCode: 409,
            errors: [
                [
                    'field' => 'tenant',
                    'message' => 'Código ou schema já está em uso.',
                ],
            ],
        );
    }
}
