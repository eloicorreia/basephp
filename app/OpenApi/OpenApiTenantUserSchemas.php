<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TenantUser',
    description: 'Vínculo entre usuário e tenant.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            description: 'Identificador interno do vínculo.',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'tenant_id',
            description: 'Identificador do tenant.',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'user_id',
            description: 'Identificador do usuário.',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'role',
            description: 'Papel do usuário dentro do tenant.',
            type: 'string',
            example: 'admin'
        ),
        new OA\Property(
            property: 'is_active',
            description: 'Indica se o vínculo está ativo.',
            type: 'boolean',
            example: true
        ),
    ]
)]
final class OpenApiTenantUserSchemas
{
}
