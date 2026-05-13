<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1HealthPaths
{
    #[OA\Get(
        path: '/api/v1/health',
        operationId: 'healthCheck',
        summary: 'Verifica se a aplicação está respondendo.',
        description: 'Endpoint público usado para health check básico da API.',
        tags: ['Health'],
        security: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aplicação disponível.',
                content: new OA\JsonContent(ref: '#/components/schemas/HealthResponse')
            ),
        ]
    )]
    public function health(): void {}
}
