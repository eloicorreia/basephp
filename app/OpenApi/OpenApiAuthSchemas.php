<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthenticatedUser',
    description: 'Usuário autenticado retornado pela API.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            description: 'Identificador interno do usuário.',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'name',
            description: 'Nome do usuário.',
            type: 'string',
            example: 'Administrador'
        ),
        new OA\Property(
            property: 'email',
            description: 'E-mail do usuário.',
            type: 'string',
            format: 'email',
            example: 'admin@local.test'
        ),
    ]
)]
final class OpenApiAuthSchemas {}
