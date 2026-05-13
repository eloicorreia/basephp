<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1AdminPaths
{
    #[OA\Get(
        path: '/api/v1/admin/ping',
        operationId: 'adminPing',
        summary: 'Verifica acesso administrativo.',
        description: 'Endpoint simples para validar autenticação, tenant e role admin.',
        tags: ['Admin'],
        security: [
            ['passport' => [], 'tenantHeader' => []],
        ],
        responses: [
            new OA\Response(response: 200, description: 'Acesso administrativo válido.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
            new OA\Response(response: 403, description: 'Acesso negado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
        ]
    )]
    public function adminPing(): void {}
}
