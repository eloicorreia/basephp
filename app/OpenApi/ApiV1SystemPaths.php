<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1SystemPaths
{
    #[OA\Get(
        path: '/api/v1/system/ping',
        operationId: 'systemPing',
        summary: 'Verifica acesso sistema-a-sistema.',
        description: 'Endpoint operacional protegido por OAuth2 Client Credentials e escopo system.health.',
        tags: ['System'],
        security: [
            ['passport' => ['system.health']],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token client credentials válido.',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Token ausente ou inválido.',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Escopo insuficiente ou token de usuário humano.',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')
            ),
        ]
    )]
    public function systemPing(): void
    {
    }
}
