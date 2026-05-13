<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

final class ApiV1EmailPaths
{
    #[OA\Get(
            path: '/api/v1/admin/emails',
            operationId: 'adminEmailIndex',
            summary: 'Lista registros de e-mail.',
            description: 'Lista e-mails registrados pela aplicação, incluindo status de envio e retentativas.',
            tags: ['Emails'],
            security: [
                ['passport' => [], 'tenantHeader' => []],
            ],
            parameters: [
                new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
                new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15)),
                new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'sent')),
                new OA\Parameter(name: 'recipient', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'usuario@local.test')),
            ],
            responses: [
                new OA\Response(response: 200, description: 'E-mails recuperados com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/PaginatedResponse')),
            ]
        )]
        public function emailIndex(): void
        {
        }

    #[OA\Get(
            path: '/api/v1/admin/emails/{emailDispatch}',
            operationId: 'adminEmailShow',
            summary: 'Consulta registro de e-mail.',
            description: 'Retorna detalhes de um registro de envio de e-mail.',
            tags: ['Emails'],
            security: [
                ['passport' => [], 'tenantHeader' => []],
            ],
            parameters: [
                new OA\Parameter(name: 'emailDispatch', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
            ],
            responses: [
                new OA\Response(response: 200, description: 'Registro recuperado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
                new OA\Response(response: 404, description: 'Registro não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
            ]
        )]
        public function emailShow(): void
        {
        }

    #[OA\Post(
            path: '/api/v1/admin/emails/send',
            operationId: 'adminEmailSend',
            summary: 'Envia e-mail.',
            description: 'Solicita envio de e-mail pela infraestrutura tenant-aware da aplicação.',
            tags: ['Emails'],
            security: [
                ['passport' => [], 'tenantHeader' => []],
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ['to', 'subject', 'body'],
                    properties: [
                        new OA\Property(property: 'to', type: 'string', example: 'usuario@local.test'),
                        new OA\Property(property: 'subject', type: 'string', example: 'Assunto do e-mail'),
                        new OA\Property(property: 'body', type: 'string', example: 'Conteúdo do e-mail.'),
                        new OA\Property(property: 'queue', type: 'string', example: 'notifications'),
                    ]
                )
            ),
            responses: [
                new OA\Response(response: 202, description: 'E-mail enfileirado com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
                new OA\Response(response: 422, description: 'Dados inválidos.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
            ]
        )]
        public function emailSend(): void
        {
        }

    #[OA\Post(
            path: '/api/v1/admin/emails/{emailDispatch}/retry',
            operationId: 'adminEmailRetry',
            summary: 'Reenvia e-mail.',
            description: 'Solicita retentativa de envio para um e-mail já registrado.',
            tags: ['Emails'],
            security: [
                ['passport' => [], 'tenantHeader' => []],
            ],
            parameters: [
                new OA\Parameter(name: 'emailDispatch', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 1)),
            ],
            responses: [
                new OA\Response(response: 200, description: 'Retentativa solicitada com sucesso.', content: new OA\JsonContent(ref: '#/components/schemas/ApiSuccessResponse')),
                new OA\Response(response: 404, description: 'Registro de e-mail não encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ApiErrorResponse')),
            ]
        )]
        public function emailRetry(): void
        {
        }
}
