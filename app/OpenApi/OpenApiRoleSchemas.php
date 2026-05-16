<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Role',
    description: 'Role global associável a usuários.',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'admin'),
        new OA\Property(property: 'name', type: 'string', example: 'Administrator'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'users_count', type: 'integer', nullable: true, example: 3),
    ]
)]
final class OpenApiRoleSchemas {}
