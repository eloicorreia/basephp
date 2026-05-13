<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    description: 'Usuário global da aplicação.',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            description: 'Identificador interno do usuário.',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'name',
            description: 'Nome do usuário.',
            type: 'string',
            example: 'Usuário Exemplo'
        ),
        new OA\Property(
            property: 'email',
            description: 'E-mail de login do usuário.',
            type: 'string',
            format: 'email',
            example: 'usuario@local.test'
        ),
        new OA\Property(
            property: 'is_active',
            description: 'Indica se o usuário está ativo.',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'must_change_password',
            description: 'Indica se o usuário deve alterar a senha antes de usar a API.',
            type: 'boolean',
            example: false
        ),
    ]
)]
#[OA\Schema(
    schema: 'UserResponse',
    description: 'Resposta padrão contendo um usuário.',
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
            ref: '#/components/schemas/User'
        ),
    ]
)]
final class OpenApiUserSchemas {}
