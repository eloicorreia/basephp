# Contrato oficial de autenticação e multi-tenant

> O README.md é a fonte central de configuração e operação. Este arquivo é material complementar e deve permanecer alinhado ao README.

## 1. Autenticação oficial
- O projeto utiliza Laravel Passport.
- O guard oficial da API é `api`.
- O driver oficial do guard `api` é `passport`.
- As rotas autenticadas devem usar `auth:api`.

## 2. OAuth clients
- Cada aplicação consumidora deve possuir seu próprio client OAuth.
- Não é permitido compartilhar o mesmo client entre múltiplas aplicações.
- Password grant é desabilitado por padrão e só pode ser reabilitado temporariamente para aplicações first-party legadas.
- Fluxos com usuário humano devem usar Authorization Code Grant com PKCE.
- Integrações sistema-a-sistema devem usar client credentials.
- Rotas client credentials não podem usar middlewares que dependem de usuário humano, como `user.active`, `tenant.access`, `password.changed` ou `role`.
- Rotas client credentials devem usar `client.credentials` e escopos explícitos, como `scope:system.health`.
- O uso excepcional de password grant deve permanecer isolado atrás da configuração `PASSPORT_ENABLE_PASSWORD_GRANT`.

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
3. throttle:api
4. user.active
5. escopos OAuth aplicáveis (`tenant.access`, `admin.full`, escopos granulares)
6. tenant.resolve
7. tenant.access
8. password.changed
9. role, quando aplicável

## 6. Roles globais e usuários
- A relação oficial entre usuário humano e role global é `users.role_id -> roles.id`.
- Cada usuário possui no máximo uma role global ativa por vez.
- A role global é usada por middlewares de autorização de alto nível, como `role:admin`.
- Criação de usuário e troca de role devem aceitar apenas roles ativas.
- A troca de role de usuário existente deve ser feita por `PATCH /api/v1/admin/users/{user}/role`.
- Toda troca de role deve registrar auditoria com `action = user.role_assigned`, contendo `before_data.role_id` e `after_data.role_id`.
- Consulta de roles disponíveis para associação deve usar `GET /api/v1/admin/roles`.
- O vínculo por tenant continua separado em `tenant_users.role_id`; ele representa o papel do usuário dentro de um tenant específico e não substitui a role global.

## 7. Headers técnicos oficiais
- `X-Request-Id`
- `X-Trace-Id`
- `X-Tenant-Id` quando aplicável

## 8. Regras obrigatórias
- Controllers não devem resolver tenant manualmente.
- Services não devem ler headers diretamente.
- O tenant ativo deve ser obtido pelo `TenantContext`.
- Não é permitido acessar dados tenant sem `tenant.resolve`.

## 9. Execuções tenant-aware fora do HTTP

Além do fluxo HTTP, o projeto adota tenancy por schema também em execuções assíncronas, eventos e comandos administrativos tenant-aware.

### 9.1. Regras obrigatórias

- Jobs tenant-aware devem transportar `tenant_id`.
- Listeners tenant-aware devem restaurar `TenantContext` e `search_path` antes de executar regra de negócio.
- Commands tenant-aware devem executar dentro de `TenantExecutionManager`.
- Nenhum job, listener ou command tenant-aware pode trocar schema manualmente fora de `TenantExecutionManager`.
- A regra oficial de tenant ativo fora do HTTP também é `status = 'active'`.

### 9.2. Fonte oficial do contexto

- O tenant atual da execução deve sempre ser obtido por `TenantContext`.
- A troca de `search_path` deve sempre ser feita por `TenantSearchPathService`.
- A orquestração de contexto tenant-aware fora do HTTP deve sempre ser feita por `TenantExecutionManager`.

### 9.3. Jobs tenant-aware

- Jobs tenant-aware devem receber `tenant_id` no construtor.
- O `handle()` do job não deve configurar schema manualmente.
- O job deve restaurar o contexto tenant antes de executar a regra principal.
- A regra de negócio do job deve operar já dentro do contexto tenant restaurado.

### 9.4. Listeners tenant-aware

- Listeners tenant-aware devem receber ou inferir `tenant_id` a partir do evento.
- O listener não deve trocar schema diretamente.
- O listener deve restaurar `TenantContext` e `search_path` antes de chamar services tenant-aware.

### 9.5. Commands tenant-aware

- Commands tenant-aware devem aceitar `tenant_id` explícito ou opção `--all`, quando aplicável.
- Commands com `--all` devem processar apenas tenants com `status = 'active'`.
- Commands tenant-aware não devem executar regra de negócio fora de `TenantExecutionManager`.

### 9.6. Exceção administrativa

Comandos estritamente administrativos que operam diretamente por schema, como rotinas de bootstrap ou migração estrutural, podem utilizar `TenantSearchPathService` diretamente, desde que isso esteja explicitamente documentado como exceção e não como padrão de negócio.
