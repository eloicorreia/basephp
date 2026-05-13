<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1TenantUserPaths
{
    #[OA\Get(
            path: '/api/v1/admin/tenant-users',
            operationId: 'adminTenantUserIndex',
            summary: 'Lista vínculos usuário/tenant.',
            description: 'Lista os vínculos entre usuários e tenants.',
            tags: ['Tenant Users'],
            security: [
                ['passport' => [], 'tenantHeader' => []],
            ],
            parameters: [
                new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
                new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15)),
                new OA\Parameter(name: 'tenant_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
                new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            ],
            responses: [
                new OA\Response(response: 200, description: 'Vínculos recuperados com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
            ]
        )]
        public function tenantUserIndex(): void
        {
        }

    #[OA\Post(
            path: '/api/v1/admin/tenant-users',
            operationId: 'adminTenantUserStore',
            summary: 'Cria vínculo usuário/tenant.',
            description: 'Vincula um usuário global a um tenant, permitindo acesso tenant-aware.',
            tags: ['Tenant Users'],
            security: [
                ['passport' => [], 'tenantHeader' => []],
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ['tenant_id', 'user_id'],
                    properties: [
                        new OA\Property(property: 'tenant_id', type: 'integer', example: 1),
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(property: 'role', type: 'string', example: 'admin'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                    ]
                )
            ),
            responses: [
                new OA\Response(response: 201, description: 'Vínculo criado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
                new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
            ]
        )]
        public function tenantUserStore(): void
        {
        }
}
