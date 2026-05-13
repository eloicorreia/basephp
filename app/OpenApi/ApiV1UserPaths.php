<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1UserPaths
{
    #[OA\Get(
        path: '/api/v1/admin/users',
        operationId: 'adminUserIndex',
        summary: 'Lista usuários.',
        description: 'Lista usuários globais da aplicação com paginação.',
        tags: ['Users'],
        security: [
            ['passport' => [], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'admin')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuários recuperados com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function userIndex(): void {}

    #[OA\Post(
        path: '/api/v1/admin/users',
        operationId: 'adminUserStore',
        summary: 'Cria usuário.',
        description: 'Cria um usuário global da aplicação.',
        tags: ['Users'],
        security: [
            ['passport' => [], 'tenantHeader' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Usuário Exemplo'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'usuario@local.test'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SenhaForte@123'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    new OA\Property(property: 'must_change_password', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuário criado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function userStore(): void {}

    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        operationId: 'adminUserShow',
        summary: 'Consulta usuário.',
        description: 'Retorna os dados de um usuário específico.',
        tags: ['Users'],
        security: [
            ['passport' => [], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuário recuperado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Usuário não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function userShow(): void {}
}
