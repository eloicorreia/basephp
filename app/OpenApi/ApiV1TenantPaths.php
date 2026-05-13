<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1TenantPaths
{
    #[OA\Get(
        path: '/api/v1/admin/tenants',
        operationId: 'adminTenantIndex',
        summary: 'Lista tenants.',
        description: 'Lista tenants cadastrados no schema público. Suporta paginação e filtros conforme implementação do controller.',
        tags: ['Tenants'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'tenants.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'tenant')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'active')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tenants recuperados com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function tenantIndex(): void {}

    #[OA\Post(
        path: '/api/v1/admin/tenants',
        operationId: 'adminTenantStore',
        summary: 'Cria um tenant.',
        description: 'Cria um novo tenant global. A criação deve respeitar unicidade de code e schema_name.',
        tags: ['Tenants'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'tenants.write'], 'tenantHeader' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name', 'schema_name'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'tenant-main'),
                    new OA\Property(property: 'name', type: 'string', example: 'Tenant Principal'),
                    new OA\Property(property: 'schema_name', type: 'string', example: 'tenant_main'),
                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Tenant criado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function tenantStore(): void {}

    #[OA\Get(
        path: '/api/v1/admin/tenants/{tenant}',
        operationId: 'adminTenantShow',
        summary: 'Consulta um tenant.',
        description: 'Retorna os dados de um tenant específico pelo identificador.',
        tags: ['Tenants'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'tenants.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tenant recuperado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Tenant não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function tenantShow(): void {}
}
