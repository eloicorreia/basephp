<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1AuthPaths
{
    #[OA\Post(
            path: '/api/v1/auth/change-password',
            operationId: 'changePassword',
            summary: 'Altera a senha do usuário autenticado.',
            description: 'Endpoint protegido por OAuth2. Usado quando o usuário precisa trocar a senha antes de acessar recursos tenant-aware.',
            tags: ['Authentication'],
            security: [
                ['passport' => []],
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ['current_password', 'password', 'password_confirmation'],
                    properties: [
                        new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'senha-atual'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NovaSenhaForte@123'),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NovaSenhaForte@123'),
                    ]
                )
            ),
            responses: [
                new OA\Response(response: 200, description: 'Senha alterada com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
                new OA\Response(response: 401, description: 'Token inválido ou ausente.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
                new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
            ]
        )]
        public function changePassword(): void
        {
        }

    #[OA\Get(
            path: '/api/v1/auth/me',
            operationId: 'authenticatedUser',
            summary: 'Retorna o usuário autenticado.',
            description: 'Endpoint tenant-aware. Exige token OAuth2 válido, usuário ativo, senha já alterada e tenant informado no header X-Tenant-Id.',
            tags: ['Authentication'],
            security: [
                ['passport' => [], 'tenantHeader' => []],
            ],
            responses: [
                new OA\Response(
                    response: 200,
                    description: 'Usuário autenticado recuperado com sucesso.',
                    content: new OA\JsonContent(
                        properties: [
                            new OA\Property(property: 'success', type: 'boolean', example: true),
                            new OA\Property(property: 'message', type: 'string', example: 'Operação realizada com sucesso.'),
                            new OA\Property(property: 'data', ref: '#/components/schemas/AuthenticatedUser'),
                        ]
                    )
                ),
                new OA\Response(response: 401, description: 'Token inválido ou ausente.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
                new OA\Response(response: 403, description: 'Usuário sem acesso ao tenant.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
            ]
        )]
        public function me(): void
        {
        }
}
