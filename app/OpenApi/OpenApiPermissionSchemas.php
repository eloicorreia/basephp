<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Permission',
    description: 'Permissão dinâmica associável a roles.',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'code', type: 'string', example: 'users.read'),
        new OA\Property(property: 'name', type: 'string', example: 'Consultar usuários'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Permite consultar usuários globais.'),
        new OA\Property(property: 'context', type: 'string', enum: ['api', 'web', 'both'], example: 'api'),
        new OA\Property(property: 'is_sensitive', type: 'boolean', example: false),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ]
)]
final class OpenApiPermissionSchemas {}
