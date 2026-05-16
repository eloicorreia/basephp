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

    #[OA\Get(
        path: '/api/v1/admin/roles/{role}/permissions',
        operationId: 'adminRolePermissions',
        summary: 'Lista permissões da role.',
        description: 'Retorna as permissões dinâmicas associadas a uma role global.',
        tags: ['Roles'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'roles.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Permissões da role recuperadas com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Role não encontrada.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function rolePermissions(): void {}

    #[OA\Put(
        path: '/api/v1/admin/roles/{role}/permissions',
        operationId: 'adminRoleSyncPermissions',
        summary: 'Sincroniza permissões da role.',
        description: 'Substitui as permissões dinâmicas associadas a uma role global e registra auditoria.',
        tags: ['Roles'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'roles.write'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['permission_ids'],
                properties: [
                    new OA\Property(
                        property: 'permission_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        example: [1, 2, 3]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Permissões da role atualizadas com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Role não encontrada.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
            new OA\Response(response: 422, description: 'Permissões inválidas.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function roleSyncPermissions(): void {}
}
