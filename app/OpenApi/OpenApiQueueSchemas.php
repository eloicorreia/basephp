<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'QueueJob',
    description: 'Registro operacional de job de fila.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'queue',
            type: 'string',
            example: 'default'
        ),
        new OA\Property(
            property: 'payload',
            description: 'Payload técnico do job. Pode ser mascarado ou resumido por segurança.',
            type: 'object',
            nullable: true
        ),
        new OA\Property(
            property: 'attempts',
            type: 'integer',
            example: 0
        ),
        new OA\Property(
            property: 'available_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            example: '2026-05-09T12:00:00Z'
        ),
    ]
)]
#[OA\Schema(
    schema: 'FailedJob',
    description: 'Registro de job com falha.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'uuid',
            type: 'string',
            example: '7e8f4d90-8d18-4fdc-95f5-5a778ad9a001'
        ),
        new OA\Property(
            property: 'connection',
            type: 'string',
            example: 'database'
        ),
        new OA\Property(
            property: 'queue',
            type: 'string',
            example: 'default'
        ),
        new OA\Property(
            property: 'exception',
            description: 'Resumo seguro da exceção.',
            type: 'string',
            nullable: true,
            example: 'RuntimeException: Falha ao processar job.'
        ),
        new OA\Property(
            property: 'failed_at',
            type: 'string',
            format: 'date-time',
            example: '2026-05-09T12:00:00Z'
        ),
    ]
)]
final class OpenApiQueueSchemas {}
