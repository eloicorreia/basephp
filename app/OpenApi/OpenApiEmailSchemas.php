<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EmailDispatch',
    description: 'Registro de envio de e-mail.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'tenant_id',
            type: 'integer',
            nullable: true,
            example: 1
        ),
        new OA\Property(
            property: 'tenant_code',
            type: 'string',
            nullable: true,
            example: 'tenant-main'
        ),
        new OA\Property(
            property: 'to',
            description: 'Destinatário do e-mail. Pode ser mascarado conforme política de segurança.',
            type: 'string',
            example: 'usuario@local.test'
        ),
        new OA\Property(
            property: 'subject',
            type: 'string',
            example: 'Assunto do e-mail'
        ),
        new OA\Property(
            property: 'status',
            type: 'string',
            example: 'queued'
        ),
        new OA\Property(
            property: 'attempts',
            type: 'integer',
            example: 0
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2026-05-09T12:00:00Z'
        ),
    ]
)]
final class OpenApiEmailSchemas
{
}
