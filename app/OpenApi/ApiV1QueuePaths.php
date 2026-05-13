<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1QueuePaths
{
    #[OA\Get(
        path: '/api/v1/admin/queues/catalog',
        operationId: 'adminQueueCatalog',
        summary: 'Consulta catálogo de filas.',
        description: 'Retorna o catálogo operacional das filas conhecidas pela aplicação.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.read'], 'tenantHeader' => []],
        ],
        responses: [
            new OA\Response(response: 200, description: 'Catálogo recuperado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
        ]
    )]
    public function queueCatalog(): void {}

    #[OA\Get(
        path: '/api/v1/admin/queues/summary',
        operationId: 'adminQueueSummary',
        summary: 'Consulta resumo das filas.',
        description: 'Retorna contadores operacionais das filas, incluindo jobs pendentes, processados ou falhos conforme implementação.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.read'], 'tenantHeader' => []],
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resumo recuperado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
        ]
    )]
    public function queueSummary(): void {}

    #[OA\Get(
        path: '/api/v1/admin/queues/jobs',
        operationId: 'adminQueueJobIndex',
        summary: 'Lista jobs de fila.',
        description: 'Lista jobs registrados no backend de filas.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'queue', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'default')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Jobs recuperados com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function queueJobIndex(): void {}

    #[OA\Get(
        path: '/api/v1/admin/queues/jobs/{job}',
        operationId: 'adminQueueJobShow',
        summary: 'Consulta job de fila.',
        description: 'Retorna detalhes de um job específico.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'job', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Job recuperado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Job não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function queueJobShow(): void {}

    #[OA\Get(
        path: '/api/v1/admin/queues/failed-jobs',
        operationId: 'adminFailedJobIndex',
        summary: 'Lista jobs falhos.',
        description: 'Lista jobs que falharam na infraestrutura de filas.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15)),
            new OA\Parameter(name: 'queue', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'default')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Jobs falhos recuperados com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
        ]
    )]
    public function failedJobIndex(): void {}

    #[OA\Get(
        path: '/api/v1/admin/queues/failed-jobs/{failedJob}',
        operationId: 'adminFailedJobShow',
        summary: 'Consulta job falho.',
        description: 'Retorna detalhes de um job falho específico.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.read'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'failedJob', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Job falho recuperado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Job falho não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function failedJobShow(): void {}

    #[OA\Post(
        path: '/api/v1/admin/queues/failed-jobs/{failedJob}/retry',
        operationId: 'adminFailedJobRetry',
        summary: 'Reprocessa job falho.',
        description: 'Solicita nova tentativa de execução para um job falho.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.write'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'failedJob', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Retentativa solicitada com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Job falho não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function failedJobRetry(): void {}

    #[OA\Delete(
        path: '/api/v1/admin/queues/failed-jobs/{failedJob}',
        operationId: 'adminFailedJobDestroy',
        summary: 'Remove registro de job falho.',
        description: 'Remove o registro de um job falho da tabela operacional.',
        tags: ['Queues'],
        security: [
            ['passport' => ['tenant.access', 'admin.full'], 'tenantHeader' => []],
            ['passport' => ['tenant.access', 'queues.write'], 'tenantHeader' => []],
        ],
        parameters: [
            new OA\Parameter(name: 'failedJob', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Job falho removido com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 404, description: 'Job falho não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function failedJobDestroy(): void {}
}
