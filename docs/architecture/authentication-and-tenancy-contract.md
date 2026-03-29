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

## 8. Execuções tenant-aware fora do HTTP

Além do fluxo HTTP, o projeto adota tenancy por schema também em execuções assíncronas, eventos e comandos administrativos tenant-aware.

### 8.1. Regras obrigatórias

- Jobs tenant-aware devem transportar `tenant_id`.
- Listeners tenant-aware devem restaurar `TenantContext` e `search_path` antes de executar regra de negócio.
- Commands tenant-aware devem executar dentro de `TenantExecutionManager`.
- Nenhum job, listener ou command tenant-aware pode trocar schema manualmente fora de `TenantExecutionManager`.
- A regra oficial de tenant ativo fora do HTTP também é `status = 'active'`.

### 8.2. Fonte oficial do contexto

- O tenant atual da execução deve sempre ser obtido por `TenantContext`.
- A troca de `search_path` deve sempre ser feita por `TenantSearchPathService`.
- A orquestração de contexto tenant-aware fora do HTTP deve sempre ser feita por `TenantExecutionManager`.

### 8.3. Jobs tenant-aware

- Jobs tenant-aware devem receber `tenant_id` no construtor.
- O `handle()` do job não deve configurar schema manualmente.
- O job deve restaurar o contexto tenant antes de executar a regra principal.
- A regra de negócio do job deve operar já dentro do contexto tenant restaurado.

### 8.4. Listeners tenant-aware

- Listeners tenant-aware devem receber ou inferir `tenant_id` a partir do evento.
- O listener não deve trocar schema diretamente.
- O listener deve restaurar `TenantContext` e `search_path` antes de chamar services tenant-aware.

### 8.5. Commands tenant-aware

- Commands tenant-aware devem aceitar `tenant_id` explícito ou opção `--all`, quando aplicável.
- Commands com `--all` devem processar apenas tenants com `status = 'active'`.
- Commands tenant-aware não devem executar regra de negócio fora de `TenantExecutionManager`.

### 8.6. Exceção administrativa

Comandos estritamente administrativos que operam diretamente por schema, como rotinas de bootstrap ou migração estrutural, podem utilizar `TenantSearchPathService` diretamente, desde que isso esteja explicitamente documentado como exceção e não como padrão de negócio.