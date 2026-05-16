<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1PermissionPaths
{
    #[OA\Get(
        path: '/api/v1/admin/permissions',
        operationId: 'adminPermissionIndex',
        summary: 'Lista permissões.',
        description: 'Lista o catálogo de permissões dinâmicas usadas por roles globais.',
        tags: ['Permissions'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'permissions.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, example: 50)),
            new OA\Parameter(name: 'active_only', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', example: true)),
            new OA\Parameter(name: 'context', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['api', 'web', 'both'], example: 'api')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Permissões recuperadas com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function permissionIndex(): void {}
}
