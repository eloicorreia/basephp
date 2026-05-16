<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1RolePaths
{
    #[OA\Get(
        path: '/api/v1/admin/roles',
        operationId: 'adminRoleIndex',
        summary: 'Lista roles.',
        description: 'Lista roles globais disponíveis para associação com usuários.',
        tags: ['Roles'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'users.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 100, minimum: 1, example: 15)),
            new OA\Parameter(name: 'active_only', in: 'query', required: false, schema: new OA\Schema(type: 'boolean', example: true)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'code', 'name', 'created_at'], example: 'name')),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'asc')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Roles recuperadas com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function roleIndex(): void {}

    #[OA\Get(
        path: '/api/v1/admin/roles/{role}',
        operationId: 'adminRoleShow',
        summary: 'Consulta role.',
        description: 'Retorna uma role global e a quantidade de usuários associados.',
        tags: ['Roles'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'users.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Role recuperada com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Role não encontrada.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function roleShow(): void {}
}
