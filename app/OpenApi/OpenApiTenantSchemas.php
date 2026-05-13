<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Tenant',
    description: 'Tenant cadastrado na aplicação.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            description: 'Identificador interno do tenant.',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'code',
            description: 'Código público usado no header X-Tenant-Id.',
            type: 'string',
            example: 'tenant-main'
        ),
        new OA\Property(
            property: 'name',
            description: 'Nome descritivo do tenant.',
            type: 'string',
            example: 'Tenant Principal'
        ),
        new OA\Property(
            property: 'schema_name',
            description: 'Nome do schema PostgreSQL associado ao tenant.',
            type: 'string',
            example: 'tenant_main'
        ),
        new OA\Property(
            property: 'status',
            description: 'Status operacional do tenant.',
            type: 'string',
            example: 'active'
        ),
    ]
)]
#[OA\Schema(
    schema: 'TenantResponse',
    description: 'Resposta padrão contendo um tenant.',
    type: 'object',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Operação realizada com sucesso.'
        ),
        new OA\Property(
            property: 'data',
            ref: '#/components/schemas/Tenant'
        ),
    ]
)]
final class OpenApiTenantSchemas
{
}
