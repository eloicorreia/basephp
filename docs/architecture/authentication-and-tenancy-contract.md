# Contrato oficial de autenticação e multi-tenant

## 1. Autenticação oficial
- O projeto utiliza Laravel Passport.
- O guard oficial da API é `api`.
- O driver oficial do guard `api` é `passport`.
- As rotas autenticadas devem usar `auth:api`.

## 2. OAuth clients
- Cada aplicação consumidora deve possuir seu próprio client OAuth.
- Não é permitido compartilhar o mesmo client entre múltiplas aplicações.
- Password grant deve ser usado apenas para aplicações first-party.
- Integrações sistema-a-sistema devem evoluir para client credentials.

## 3. Tenant
- O tenant é obrigatório nas rotas que dependem de contexto tenant.
- O header oficial é `X-Tenant-Id`.
- Apesar do nome do header, o valor enviado deve ser o campo `code` do tenant.
- O tenant deve possuir status `active`.

## 4. Resolução de tenant
- A resolução de tenant deve ocorrer exclusivamente no middleware `tenant.resolve`.
- O middleware oficial é `ResolveTenantMiddleware`.
- O schema do tenant deve ser configurado pelo `TenantSearchPathService`.
- O contexto corrente deve ser armazenado no `TenantContext`.

## 5. Ordem de execução esperada
1. RequestContextMiddleware
2. auth:api
3. user.active
4. tenant.resolve
5. tenant.access
6. password.changed
7. role, quando aplicável

## 6. Headers técnicos oficiais
- `X-Request-Id`
- `X-Trace-Id`
- `X-Tenant-Id` quando aplicável

## 7. Regras obrigatórias
- Controllers não devem resolver tenant manualmente.
- Services não devem ler headers diretamente.
- O tenant ativo deve ser obtido pelo `TenantContext`.
- Não é permitido acessar dados tenant sem `tenant.resolve`.