<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'HealthResponse',
    description: 'Resposta do endpoint de health check.',
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
            type: 'object',
            required: ['status'],
            properties: [
                new OA\Property(
                    property: 'status',
                    description: 'Estado básico da aplicação.',
                    type: 'string',
                    example: 'ok'
                ),
            ]
        ),
    ]
)]
final class OpenApiHealthSchemas
{
}
