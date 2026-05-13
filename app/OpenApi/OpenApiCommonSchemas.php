<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ApiSuccessResponse',
    description: 'Envelope padrão para respostas de sucesso.',
    type: 'object',
    required: ['success', 'message', 'data'],
    properties: [
        new OA\Property(
            property: 'success',
            description: 'Indica se a operação foi concluída com sucesso.',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'message',
            description: 'Mensagem padronizada para o consumidor da API.',
            type: 'string',
            example: 'Operação realizada com sucesso.'
        ),
        new OA\Property(
            property: 'data',
            description: 'Objeto de retorno da operação.',
            type: 'object',
            nullable: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'ApiErrorResponse',
    description: 'Envelope padrão para respostas de erro.',
    type: 'object',
    required: ['success', 'message', 'errors'],
    properties: [
        new OA\Property(
            property: 'success',
            description: 'Indica que a operação falhou.',
            type: 'boolean',
            example: false
        ),
        new OA\Property(
            property: 'message',
            description: 'Mensagem principal do erro.',
            type: 'string',
            example: 'Erro ao processar a requisição.'
        ),
        new OA\Property(
            property: 'errors',
            description: 'Lista de erros detalhados, quando aplicável.',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'property',
                        description: 'Campo relacionado ao erro, quando aplicável.',
                        type: 'string',
                        example: 'email'
                    ),
                    new OA\Property(
                        property: 'type',
                        description: 'Tipo técnico ou funcional do erro.',
                        type: 'string',
                        example: 'VALIDATION'
                    ),
                    new OA\Property(
                        property: 'message',
                        description: 'Descrição detalhada do erro.',
                        type: 'string',
                        example: 'O campo email é obrigatório.'
                    ),
                    new OA\Property(
                        property: 'exception',
                        description: 'Classe da exceção tratada, quando exposta de forma segura.',
                        type: 'string',
                        example: 'ValidationException'
                    ),
                ]
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    description: 'Metadados de paginação usados em listagens.',
    type: 'object',
    required: ['page', 'per_page', 'total', 'last_page'],
    properties: [
        new OA\Property(
            property: 'page',
            description: 'Página atual.',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'per_page',
            description: 'Quantidade de registros por página.',
            type: 'integer',
            example: 15
        ),
        new OA\Property(
            property: 'total',
            description: 'Total de registros encontrados.',
            type: 'integer',
            example: 100
        ),
        new OA\Property(
            property: 'last_page',
            description: 'Última página disponível.',
            type: 'integer',
            example: 7
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaginatedResponse',
    description: 'Envelope padrão para respostas paginadas.',
    type: 'object',
    required: ['success', 'message', 'data', 'meta'],
    properties: [
        new OA\Property(
            property: 'success',
            type: 'boolean',
            example: true
        ),
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Dados recuperados com sucesso.'
        ),
        new OA\Property(
            property: 'data',
            description: 'Lista de registros retornados.',
            type: 'array',
            items: new OA\Items(type: 'object')
        ),
        new OA\Property(
            property: 'meta',
            ref: '#/components/schemas/PaginationMeta'
        ),
    ]
)]
final class OpenApiCommonSchemas {}
