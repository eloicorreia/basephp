<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.3'
)]
#[OA\Info(
    version: '1.0.0',
    title: 'BasePHP API',
    description: 'Documentação oficial da API backend-only BasePHP.

Esta API foi construída em PHP 8.3, Laravel 12 e PostgreSQL, com autenticação OAuth2 via Laravel Passport, multitenancy por schema, logs persistidos em banco e rastreabilidade por request_id e trace_id.

Fluxo de uso no Swagger UI com Authorization Code + PKCE:
1. Clique em Authorize.
2. Em passport, selecione authorizationCode, informe client_id público e siga o callback OAuth.
3. Em tenantHeader, informe o código do tenant, por exemplo tenant-main.
4. Execute os endpoints protegidos diretamente pela interface.

Fluxo sistema-a-sistema:
- Use clientCredentials com client_id, client_secret e escopos operacionais explícitos.
- Tokens client credentials não representam usuário humano e não devem acessar rotas tenant-aware de usuário.
- Password grant é desabilitado por padrão e só pode ser reabilitado temporariamente para clients legados.

Endpoints tenant-aware exigem:
- Authorization: Bearer {access_token}
- X-Tenant-Id: {tenant-code}

Endpoints administrativos exigem usuário autenticado, tenant válido e permissão administrativa.

Observações de segurança:
- Nunca informe token real, senha real ou client_secret real em exemplos públicos.
- O header Authorization não deve ser persistido em logs.
- O header X-Tenant-Id é obrigatório para endpoints tenant-aware.
- Payloads sensíveis devem ser mascarados nos logs persistidos.'
)]
#[OA\Server(
    url: '/api/v1',
    description: 'API REST versionada v1'
)]
#[OA\Server(
    url: '/',
    description: 'Raiz da aplicação, usada por endpoints auxiliares como OAuth2 Passport'
)]
#[OA\Tag(
    name: 'Health',
    description: 'Verificação básica de disponibilidade da aplicação.'
)]
#[OA\Tag(
    name: 'Authentication',
    description: 'Endpoints relacionados ao usuário autenticado, sessão OAuth2 e alteração de senha.'
)]
#[OA\Tag(
    name: 'System',
    description: 'Endpoints operacionais protegidos por client credentials.'
)]
#[OA\Tag(
    name: 'Admin',
    description: 'Endpoints administrativos protegidos por autenticação, tenant e autorização.'
)]
#[OA\Tag(
    name: 'Tenants',
    description: 'Administração de tenants globais da aplicação.'
)]
#[OA\Tag(
    name: 'Users',
    description: 'Administração de usuários globais da aplicação.'
)]
#[OA\Tag(
    name: 'Tenant Users',
    description: 'Administração de vínculos entre usuários e tenants.'
)]
#[OA\Tag(
    name: 'Queues',
    description: 'Consulta operacional de filas, jobs pendentes e jobs com falha.'
)]
#[OA\Tag(
    name: 'Emails',
    description: 'Consulta, envio, auditoria e retentativa de e-mails registrados pela aplicação.'
)]
final class OpenApi
{
}
