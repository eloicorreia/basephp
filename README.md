# BasePHP

Base arquitetural para APIs e módulo web administrativo em **PHP 8.3 + Laravel 12 + PostgreSQL**, preparada para:

- APIs REST versionadas
- frontend administrativo em Blade
- autenticação OAuth2 com Laravel Passport
- autenticação web por sessão
- multitenancy por **schema no PostgreSQL**
- logging persistido em banco
- observabilidade com `request_id` e `trace_id`
- execução tenant-aware no HTTP e fora da requisição web
- testes de infraestrutura e regressão

---

# 1. Objetivo do projeto

Este projeto é uma base técnica para construção de aplicações Laravel com **APIs REST** e **módulo web administrativo em Blade**, orientadas à exposição de serviços para consumo por outras aplicações e à operação administrativa segura.

A base foi estruturada para priorizar:

- segurança
- contratos estáveis
- baixo acoplamento
- previsibilidade arquitetural
- rastreabilidade
- observabilidade
- suporte a multitenancy
- manutenção de longo prazo

Esta base **não é apenas um esqueleto Laravel**.  
Ela já define regras arquiteturais, contratos de execução, middlewares obrigatórios, padrão de autenticação, padrão de tenancy, padrão de logging e suíte de testes para proteger a infraestrutura.

---

# 2. Stack oficial

## Runtime

- **PHP 8.3**
- **Laravel 12**
- **PostgreSQL**
- **Laravel Passport**
- **Blade** para o módulo web administrativo
- **gRPC / protobuf** preparado na base
- **PHPUnit** para testes

## Banco de dados

- banco oficial: **PostgreSQL**
- schema global padrão: `public`
- um schema por tenant
- `search_path` controlado pela aplicação

## Estilo de aplicação

- APIs REST versionadas
- módulo web administrativo em Blade
- autenticação OAuth2
- autenticação web por sessão
- multitenancy por schema
- serviços orientados a integração

---

# 3. Princípios arquiteturais

A base segue estes princípios obrigatórios:

- Controllers apenas orquestram entrada e saída
- Requests validam entrada
- Services concentram regra de negócio
- Resources padronizam respostas JSON
- Exceptions padronizam falhas previsíveis
- Middlewares aplicam autenticação, autorização, tenancy, correlação e logging
- Integrations/Clients isolam comunicação externa
- Logs são persistidos em tabela
- Execuções tenant-aware não manipulam schema manualmente fora do executor oficial

## Contratos rígidos da base oficial

Esta base pode ser usada como origem de novos projetos, mas os itens abaixo devem ser tratados como contrato rígido. Mudanças nesses pontos precisam vir acompanhadas de revisão arquitetural, atualização do README e ajuste dos testes de contrato em `tests/Feature/Architecture/BaseContractArchitectureTest.php`.

### Contrato REST

- toda API funcional deve ficar sob `/api/v1`
- controllers de API devem responder por `App\Support\Http\ApiResponse`
- controllers não devem usar `response()->json()` diretamente
- respostas de sucesso, erro e paginação devem manter os envelopes documentados na seção **APIs REST**
- erro global deve sair pelo exception handler central em `bootstrap/app.php`

### Contrato Web Administrativo

- o módulo web administrativo usa Blade e rotas em `/admin`
- autenticação web usa o guard `web` com sessão, nunca token OAuth
- controllers web retornam views ou redirects, não envelopes JSON de API
- permissões web ficam em `App\Support\Web\WebAdminPermissions`
- rotas web administrativas usam middleware `auth:web` e `web.permission`
- o padrão visual oficial é o template `master` de `https://github.com/eloicorreia/templateweb`
- visualização de logs deve passar por service próprio e mascaramento antes de renderizar

### Contrato OAuth2

- todos os scopes oficiais ficam em `App\Support\Auth\OAuthScopes`
- `App\Providers\AppServiceProvider` registra scopes a partir de `OAuthScopes`
- `config/l5-swagger.php` documenta scopes a partir de `OAuthScopes`
- rotas devem usar `OAuthScopes::scope()` ou `OAuthScopes::any()`, sem string solta de scope
- usuários humanos usam Authorization Code + PKCE
- integrações sistema-a-sistema usam Client Credentials
- password grant fica desabilitado por padrão

### Contrato de tenancy

- rotas tenant-aware exigem `auth:api`, o scope OAuth `tenant.access`, o middleware `tenant.resolve`, o middleware `tenant.access` e `password.changed`
- o scope OAuth `tenant.access` autoriza o token a acessar área tenant-aware
- o middleware `tenant.access` valida o vínculo ativo entre usuário autenticado e tenant resolvido
- controllers não leem `X-Tenant-Id`, não manipulam `TenantContext` e não executam `SET search_path`
- troca de `search_path` fica restrita aos serviços de infraestrutura de tenancy
- execuções fora do HTTP devem passar por `TenantExecutionManager`
- políticas de segurança por tenant, como contador de falhas e bloqueio, persistem estado em `tenant_user_security_states`
- os campos globais `users.failed_login_attempts`, `users.locked_until` e `users.locked_by_admin` não fazem parte da modelagem oficial

### Contrato de logging e observabilidade

- request logging passa por `ApiRequestLoggingMiddleware` e `ApiRequestLogger`
- payloads sensíveis devem passar pela sanitização recursiva oficial
- persistência de query/body/response body deve ser exceção consciente em produção
- toda tabela operacional de log precisa ter retenção configurada
- `request_id` e `trace_id` devem existir em toda requisição HTTP

### Contrato de exception handler

- validação, autenticação, autorização, exceções de domínio, HTTP exceptions e falhas inesperadas devem ser convertidas no envelope JSON padrão
- exception handler não deve expor detalhes internos em erro 500
- toda exceção relevante deve tentar persistir log técnico sem impedir a resposta da API

### Contrato de testes

- testes de arquitetura são parte da base, não documentação opcional
- qualquer novo módulo deve ter testes de feature cobrindo autenticação, autorização, tenancy e envelope de resposta
- mudanças em OAuth, tenancy, logging, exception handler ou responses devem atualizar os testes de contrato antes de serem aceitas

---

# 4. Estrutura do projeto

## Diretórios principais

```text
app/
├── Console/
├── Contracts/
├── DTO/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
│   └── Concerns/
├── Models/
├── Services/
│   ├── Admin/
│   │   └── Web/
│   ├── Logging/
│   └── Tenant/
├── Support/
│   ├── Web/
│   └── Tenant/
bootstrap/
config/
database/
├── migrations/
│   ├── public/
│   └── tenant/
docs/
└── architecture/
public/
└── vendor/templateweb/master/
resources/
└── views/admin/
routes/
tests/
```

## Documentação arquitetural

O **README.md é a fonte central de configuração e operação da base**.
A pasta `docs/architecture` pode existir como material complementar, mas qualquer regra obrigatória de segurança, OAuth, tenancy, logging, CI, testes ou deploy deve estar refletida aqui para evitar configuração perdida entre arquivos.

---

# 5. APIs REST

## Prefixo oficial

Todas as rotas REST devem usar versionamento:

```text
/api/v1
```

## Exemplos

- `GET /api/v1/health`
- `POST /api/v1/auth/change-password`
- `GET /api/v1/auth/me`
- `GET /api/v1/admin/ping`

## Padrão de resposta

### Sucesso

```json
{
  "success": true,
  "message": "Operação realizada com sucesso.",
  "data": {}
}
```

### Erro

```json
{
  "success": false,
  "message": "Erro ao processar a requisição.",
  "errors": []
}
```

### Paginação

```json
{
  "success": true,
  "message": "Dados recuperados com sucesso.",
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

## Datas em Resources

Campos REST canônicos como `created_at` e `updated_at` devem permanecer estáveis em ISO/UTC para integrações e clientes externos. Formatos humanos configuráveis pelo tenant devem ser expostos apenas em campos adicionais com sufixo `_formatted`, por exemplo `created_at_formatted` e `updated_at_formatted`, seguindo as runtime settings do tenant.

---

# 6. Módulo web administrativo

## Objetivo

O módulo web administrativo existe para visualizações operacionais internas, como dashboard e consulta de logs. Ele é parte oficial da base e deve evoluir separado da API pública.

## Rotas oficiais

```text
GET  /admin/login
POST /admin/login
GET  /admin/forgot-password
POST /admin/forgot-password
GET  /admin/reset-password/{token}
POST /admin/reset-password
POST /admin/logout
GET  /admin/password/change
PUT  /admin/password/change
GET  /admin
GET  /admin/logs/api-requests
GET  /admin/logs/api-requests/{apiRequestLog}
GET  /admin/logs/api-requests/{apiRequestLog}/payload
```

## Autenticação web tenant-aware

O formulário `/admin/login` aceita o campo `tenant_code`. Quando `tenant_code` é informado, o login administrativo é tenant-aware e aplica as configurações salvas em **Sistema > Segurança** e **Sistema > Senhas**. O header `X-Tenant-Id` continua aceito para compatibilidade com testes e automações, mas a tela real não depende mais de header invisível ao usuário.

No login tenant-aware, o sistema valida tenant ativo, usuário ativo, vínculo ativo em `tenant_users`, permissão administrativa, `allowed_ip_ranges`, bloqueio em `tenant_user_security_states`, tentativas máximas, duração de bloqueio, senha temporária expirada, expiração de senha e troca obrigatória no primeiro login. Falhas incrementam o contador apenas do tenant atual; sucesso zera apenas o contador do tenant atual e grava `admin_tenant_code`, `admin_login_at`, `admin_password_changed_at` e `admin_web_session_id` na sessão web.

O login sem `tenant_code` e sem `X-Tenant-Id` permanece como fluxo global legado do painel. Esse modo não aplica políticas tenant-aware porque não há tenant resolvido; use-o somente para compatibilidade operacional enquanto o acesso administrativo tenant-aware estiver sendo adotado.

## Sessão web tenant-aware

Após login tenant-aware, as rotas administrativas normais passam pelo middleware `web.tenant-security`. Ele aplica continuamente vínculo ativo usuário x tenant, allowlist de IP, bloqueio tenant-aware, `session_lifetime_minutes`, `idle_timeout_minutes`, `force_single_session_per_user` e `logout_on_password_change`.

Sessões web tenant-aware são persistidas em `tenant_user_web_sessions`. Quando `force_single_session_per_user=true`, sessões anteriores do mesmo usuário no mesmo tenant são revogadas e deixam de acessar o painel. No logout, a sessão atual é marcada como revogada, `admin_tenant_code` e metadados de sessão são limpos, a sessão Laravel é invalidada e a operação é auditada/logada com usuário, tenant, IP e identificador da sessão.

## Troca de senha

As rotas `GET /admin/password/change` e `PUT /admin/password/change` atendem tanto troca voluntária quanto troca obrigatória. Usuários com `must_change_password=true`, primeiro login obrigatório, senha expirada ou senha temporária válida com troca exigida são redirecionados para essa tela e não acessam as demais telas administrativas até concluir a alteração.

A troca de senha web usa o tenant salvo em `admin_tenant_code` e respeita tamanho mínimo/máximo, maiúscula, minúscula, número, símbolo, senhas comuns, dados pessoais, histórico, expiração e `logout_on_password_change`. Ao concluir, atualiza `users.password`, `users.password_changed_at`, `users.must_change_password=false`, registra histórico no schema do tenant quando configurado e audita a operação.

Na API, `POST /api/v1/auth/change-password` mantém o contrato JSON existente. Quando `X-Tenant-Id` é informado, a API exige vínculo ativo do usuário com o tenant, aplica a política de senha tenant-aware e registra histórico no schema do tenant. Sem `X-Tenant-Id`, permanece o comportamento global legado validado apenas pelo contrato base do endpoint.

## Template oficial

O padrão visual do módulo web deve usar como base:

```text
https://github.com/eloicorreia/templateweb
```

A pasta oficial de referência é:

```text
master
```

No projeto, os assets mínimos ficam em:

```text
public/vendor/templateweb/master/assets
```

O contrato do template também fica em:

```text
config/admin_web.php
```

O painel administrativo usa os assets estáticos do `templateweb/master` em `public/vendor/templateweb/master/assets`. As views administrativas não devem usar `@vite`; esse contrato é validado por:

```bash
php artisan web:assets:check
composer web:check
```

O projeto ainda mantém Vite para assets gerais do Laravel. Por isso o CI executa `npm ci` e `npm run build`. Se o painel administrativo passar a usar Vite no futuro, a decisão deve ser formalizada no README, no CI e no contrato de assets.

O contrato visual atual usa as fontes `HKGrotesk` do template e os pacotes de ícones referenciados por `css/icons.min.css`, incluindo `Remix Icon`, `Material Design Icons`, `Boxicons` e `Line Awesome`. A folha `css/admin-contract.css` centraliza a escolha de fonte e ajustes pequenos da base, para evitar personalizações espalhadas pelas views.

O dashboard administrativo foi separado para servir como base de composição das próximas telas:

- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/admin-auth.blade.php`
- `resources/views/admin/partials/*`
- `resources/views/components/admin/page-title.blade.php`
- `resources/views/components/admin/metric-card.blade.php`
- `resources/views/components/admin/action-card.blade.php`

## Separação entre autenticação API e Web

API e Web usam mecanismos diferentes:

| Área | Guard | Credencial | Uso |
| --- | --- | --- | --- |
| API | `api` | Bearer token Passport | Clients externos, SPAs, mobile, integrações |
| Web admin | `web` | Sessão Laravel | Painel administrativo Blade |

Regras obrigatórias:

- rotas `/api/v1/*` usam `auth:api`, scopes OAuth e respostas JSON
- rotas `/admin/*` usam `auth:web`, sessão, CSRF e views Blade
- sessão web não autentica API
- token OAuth não autentica painel Blade
- login web administrativo é exclusivo para usuários ativos com permissão web administrativa
- recuperação de senha do painel administrativo usa rotas `guest:web`, resposta genérica e só envia/redefine senha para usuários permitidos por `WebAdminPermissions::ACCESS`

## Permissões web oficiais

Permissões web não são scopes OAuth. Elas ficam em:

```text
App\Support\Web\WebAdminPermissions
```

Permissões iniciais:

| Permissão | Uso |
| --- | --- |
| `admin.web.access` | Acesso ao módulo web administrativo. |
| `admin.dashboard.view` | Visualização do dashboard administrativo. |
| `admin.logs.api_requests.view` | Consulta de logs de requisições da API. |
| `admin.logs.api_requests.payloads.view` | Visualização detalhada de payloads mascarados dos logs da API. |

O middleware oficial é:

```text
web.permission
```

## Padrão das telas administrativas

- usar layout vertical do template `templateweb/master`
- priorizar interface densa, operacional e escaneável
- usar cards apenas para métricas ou blocos funcionais
- usar tabelas para listagens administrativas
- evitar landing page, hero marketing, textos explicativos longos e elementos decorativos soltos
- toda tela administrativa deve ter teste de feature cobrindo autenticação e autorização

## Consulta e visualização de logs

Consulta de logs administrativos deve passar por service próprio:

```text
App\Services\Admin\Web\AdminLogQueryService
```

A política de mascaramento da visualização fica em:

```text
App\Services\Admin\Web\VisibleLogSanitizer
```

Regras obrigatórias:

- nunca renderizar payload bruto de log diretamente na view
- aplicar mascaramento novamente na visualização, mesmo que o dado já tenha sido sanitizado na escrita
- `Authorization`, `X-Api-Key`, tokens, senhas, cookies, secrets e credenciais devem aparecer como `***`
- views Blade devem usar escaping padrão `{{ }}` para qualquer dado vindo de log
- listagens de logs devem exigir filtro de período, com período padrão e limite máximo definidos em `config/admin_web.php`
- listagens de logs devem usar paginação obrigatória e limite máximo de itens por página
- detalhe do log não deve renderizar payload completo automaticamente
- payload detalhado deve ficar em rota própria, protegido por `admin.logs.api_requests.payloads.view`
- acesso ao detalhe do log e ao payload detalhado deve gerar registro em `audit_logs`
- campos longos exibidos na interface devem ser truncados pelo service de visualização

## Auditoria web administrativa

Ações sensíveis no painel administrativo devem gerar trilha em `audit_logs` via:

```text
App\Services\Admin\Web\AdminWebAuditService
```

Ações já contratadas:

- login administrativo com sucesso
- tentativa de login administrativo inválida
- logout administrativo
- tentativa negada por falta de permissão web
- acesso ao detalhe de log
- acesso ao payload detalhado de log
- solicitação de recuperação de senha administrativa
- redefinição de senha administrativa concluída

Ações administrativas futuras sobre filas, tenants, usuários e configurações devem seguir o mesmo padrão antes de entrar na base oficial.

---

# 7. Autenticação OAuth2 com Passport

## Guard oficial

O guard oficial da API é:

- `api`

Ele utiliza:

- driver `passport`

## Fluxo oficial atual

A base está preparada para autenticação via OAuth2 com Laravel Passport.

O fluxo oficial para usuários humanos é **Authorization Code Grant com PKCE**.
O fluxo oficial para integrações sistema-a-sistema é **Client Credentials**.

O **password grant** fica desabilitado por padrão e deve ser tratado apenas como compatibilidade excepcional para clients legados.

## Quando usar

### Password grant
Não use para novos clients.

Pode ser reabilitado temporariamente apenas como ponte de compatibilidade para aplicação first-party legada e controlada pela própria plataforma.

O uso fica condicionado à variável:

```env
PASSPORT_ENABLE_PASSWORD_GRANT=true
```

Em novos ambientes, mantenha:

```env
PASSPORT_ENABLE_PASSWORD_GRANT=false
```

### Authorization Code + PKCE
Use para aplicações com usuário humano, incluindo SPAs, mobile apps e frontends first-party que não devem manipular senha diretamente no client.

Esse fluxo exige redirecionamento para a camada web/sessão que autentica o usuário e aprova a autorização. A API não deve receber senha diretamente de SPA ou aplicação mobile.

### Client credentials
Use para integração sistema-a-sistema, quando não houver usuário humano autenticado.

## Escopos oficiais

Os escopos são centralizados em `App\Support\Auth\OAuthScopes`, registrados em `App\Providers\AppServiceProvider`, documentados em `config/l5-swagger.php` e aplicados diretamente nas rotas. Ao criar um client OAuth, conceda somente os escopos necessários.

| Escopo | Uso |
| --- | --- |
| `user.profile` | Consultar o usuário autenticado em `/api/v1/auth/me`. |
| `user.password.change` | Alterar a própria senha em `/api/v1/auth/change-password`. |
| `tenant.access` | Acessar qualquer rota tenant-aware de usuário. |
| `admin.full` | Acesso administrativo amplo, usado como escopo guarda-chuva. |
| `tenants.read` / `tenants.write` | Consultar/criar/manter tenants. |
| `users.read` / `users.write` | Consultar/criar/manter usuários globais. |
| `roles.read` / `roles.write` | Consultar roles e manter permissões vinculadas a roles. |
| `permissions.read` / `permissions.write` | Consultar/manter catálogo de permissões. |
| `tenant.users.read` / `tenant.users.write` | Consultar/criar/manter vínculos usuário x tenant. |
| `queues.read` / `queues.write` | Consultar filas e executar ações operacionais. |
| `emails.read` / `emails.write` | Consultar, enviar e reprocessar e-mails. |
| `system.health` | Acesso sistema-a-sistema ao endpoint `/api/v1/system/ping`. |

Regras importantes:

- rotas tenant-aware exigem o scope OAuth `tenant.access` e o middleware `tenant.access`
- rotas administrativas exigem role `admin`, permissão dinâmica no banco e escopo específico ou `admin.full`
- rotas client credentials não podem depender de usuário humano
- `system.health` deve ser usado apenas com client credentials

## Roles globais de usuários

A associação oficial entre usuários humanos e roles globais é `users.role_id -> roles.id`.
Cada usuário possui uma única role global por vez, usada por middlewares como `role:admin`.
O papel do usuário dentro de um tenant específico continua separado em `tenant_users.role_id`.
Roles inativas não autorizam middlewares `role:*` e não podem ser atribuídas a usuários.

Endpoints administrativos disponíveis:

| Endpoint | Uso |
| --- | --- |
| `GET /api/v1/admin/roles` | Lista roles globais ativas para seleção em telas administrativas. |
| `GET /api/v1/admin/roles/{role}` | Consulta uma role global e sua quantidade de usuários associados. |
| `GET /api/v1/admin/roles/{role}/permissions` | Lista permissões vinculadas à role. |
| `PUT /api/v1/admin/roles/{role}/permissions` | Sincroniza permissões vinculadas à role. |
| `GET /api/v1/admin/permissions` | Lista o catálogo de permissões dinâmicas. |
| `PATCH /api/v1/admin/users/{user}/role` | Troca a role global de um usuário existente. |

`GET /api/v1/admin/roles` aceita `per_page` com máximo 100, `active_only`, `sort`
controlado por allowlist (`id`, `code`, `name`, `created_at`) e `direction` (`asc` ou `desc`).

Criação e troca de role aceitam apenas roles ativas. A troca registra auditoria em `audit_logs`
com `action = user.role_assigned`, preservando snapshot de `role_id`, `role_code` e `role_name`
em `before_data` e `after_data`.

Regras de segurança:

- usuário não pode alterar a própria role pela API administrativa
- não é permitido remover ou rebaixar o último usuário ativo com role global `admin` ativa
- a checagem do último admin usa bloqueio pessimista na transação para serializar trocas concorrentes de roles administrativas
- roles autorizam ações por meio de `role_permissions`; `admin.full` é a permissão guarda-chuva administrativa
- alteração de permissões de role registra auditoria em `audit_logs` com `action = role.permissions_synced`
- consultas de catálogo de roles passam por `RoleService`, deixando o controller fino para filtros, regras e auditoria futuras

---

# 8. Como instalar e subir localmente

## 8.1. Clonar o projeto

```bash
git clone <url-do-repositorio>
cd basephp
```

## 8.2. Instalar dependências

```bash
composer install
```

## 8.3. Criar `.env`

```bash
cp .env.example .env
```

## 8.4. Gerar chave da aplicação

```bash
php artisan key:generate
```

## 8.5. Configurar banco PostgreSQL

Exemplo mínimo no `.env`:

```env
APP_NAME=BasePHP
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=basephp
DB_USERNAME=postgres
DB_PASSWORD=postgres
DB_SCHEMA=public
DB_SSLMODE=prefer

PASSPORT_ENABLE_PASSWORD_GRANT=false

ADMIN_WEB_ENABLED=true

API_REQUEST_LOGGING_ENABLED=true
API_REQUEST_LOGGING_STORE_HEADERS=true
API_REQUEST_LOGGING_STORE_QUERY=true
API_REQUEST_LOGGING_STORE_REQUEST_BODY=true
API_REQUEST_LOGGING_STORE_RESPONSE_BODY=true
API_REQUEST_LOGGING_MAX_TEXT_LENGTH=2000

LOG_PRUNING_ENABLED=true
LOG_PRUNING_SCHEDULE_TIME=02:15
LOG_RETENTION_API_REQUEST_DAYS=30
LOG_RETENTION_SYSTEM_DAYS=90
LOG_RETENTION_AUTHENTICATION_DAYS=180
LOG_RETENTION_AUDIT_DAYS=365
LOG_RETENTION_QUEUE_EXECUTION_DAYS=30
LOG_RETENTION_QUEUE_JOB_DAYS=30
LOG_RETENTION_QUEUE_WORKER_DAYS=30

L5_SWAGGER_PUBLIC=true
L5_SWAGGER_ALLOWED_IPS=127.0.0.1,::1
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=false

FILESYSTEM_LOCAL_SERVE=false
```

Em produção, mantenha `APP_DEBUG=false`, `PASSPORT_ENABLE_PASSWORD_GRANT=false`, `L5_SWAGGER_PUBLIC=false`, `FILESYSTEM_LOCAL_SERVE=false` e evite persistir bodies completos de request/response salvo necessidade auditável.

## 8.6. Rodar migrations

```bash
php artisan migrate
```

## 8.7. Instalar chaves do Passport

Se for a primeira execução do ambiente, gere as chaves do Passport:

```bash
php artisan passport:keys
```

Se precisar recriar:

```bash
php artisan passport:keys --force
```

## 8.8. Criar clients OAuth

```bash
php artisan passport:client --public
```

Use esse formato para clients com usuário humano que usarão Authorization Code + PKCE.

Esse command vai solicitar:

- nome do client
- redirect

Para ambiente local, normalmente:

- nome: `Local PKCE Client`
- redirect: `http://localhost:3000/oauth/callback`

Ao final, ele retorna:

- `client_id`

Clients públicos usados com PKCE não possuem `client_secret`.

Para integração sistema-a-sistema, crie um client credentials:

```bash
php artisan passport:client --client
```

Esse command retorna `client_id` e `client_secret`. Guarde o secret em cofre de segredo ou variável de ambiente segura.

## 8.9. Seeders

Se o projeto exigir dados base:

```bash
php artisan db:seed
```

Ou seed específico:

```bash
php artisan db:seed --class=TenantSeeder
```

## 8.10. Subir servidor local

```bash
php artisan serve
```

## 8.11. Gerar documentação OpenAPI

```bash
php artisan l5-swagger:generate
```

Os artefatos gerados em `storage/api-docs`, como `api-docs.json` e `api-docs.yaml`, **não são versionados**. Eles devem ser gerados localmente quando necessário e sempre no CI, evitando manutenção manual de documentação compilada.

A documentação fica disponível em:

```text
/docs
/api/documentation
```

Por padrão, a documentação é pública apenas em `local` e `testing`. Em produção, use:

```env
L5_SWAGGER_PUBLIC=false
L5_SWAGGER_ALLOWED_IPS=10.0.0.10,10.0.0.11
L5_SWAGGER_UI_PERSIST_AUTHORIZATION=false
```

Se `L5_SWAGGER_PUBLIC=false` e o IP não estiver permitido, a documentação responde `404` para não expor sua existência.

---

# 9. Como autenticar e gerar token OAuth

## 9.1. Usuário humano com Authorization Code + PKCE

O client deve gerar `code_verifier` e `code_challenge` no padrão PKCE.

## Authorization endpoint

```text
GET /oauth/authorize
```

Parâmetros principais:

- `response_type=code`
- `client_id`
- `redirect_uri`
- `scope=user.profile tenant.access`
- `code_challenge`
- `code_challenge_method=S256`

Para rotas administrativas, inclua apenas os escopos necessários. Exemplo:

```text
scope=tenant.access users.read users.write
```

Ou, para um client administrativo interno e altamente confiável:

```text
scope=tenant.access admin.full
```

## Token endpoint

```text
POST /oauth/token
```

Exemplo de payload:

```json
{
  "grant_type": "authorization_code",
  "client_id": "SEU_CLIENT_ID_PUBLICO",
  "redirect_uri": "http://localhost:3000/oauth/callback",
  "code_verifier": "CODE_VERIFIER_ORIGINAL",
  "code": "CODIGO_RECEBIDO_NO_CALLBACK"
}
```

## 9.2. Integração sistema-a-sistema com Client Credentials

## Endpoint

```text
POST /oauth/token
```

## Exemplo de payload

```json
{
  "grant_type": "client_credentials",
  "client_id": "SEU_CLIENT_ID",
  "client_secret": "SEU_CLIENT_SECRET",
  "scope": "system.health"
}
```

## Exemplo com curl

```bash
curl --request POST \
  --url http://localhost:8000/oauth/token \
  --header 'Content-Type: application/json' \
  --data '{
    "grant_type": "client_credentials",
    "client_id": "SEU_CLIENT_ID",
    "client_secret": "SEU_CLIENT_SECRET",
    "scope": "system.health"
  }'
```

## Resposta esperada

```json
{
  "token_type": "Bearer",
  "expires_in": 31536000,
  "access_token": "..."
}
```

O grant `client_credentials` não emite `refresh_token`; quando expirar, o integrador solicita um novo token com `client_id` e `client_secret`.

## Como consumir rota protegida

Depois de obter um token de usuário via PKCE:

```bash
curl --request GET \
  --url http://localhost:8000/api/v1/auth/me \
  --header 'Authorization: Bearer SEU_ACCESS_TOKEN' \
  --header 'X-Tenant-Id: tenant-main'
```

Depois de obter um token client credentials:

```bash
curl --request GET \
  --url http://localhost:8000/api/v1/system/ping \
  --header 'Authorization: Bearer SEU_ACCESS_TOKEN'
```

---

# 10. Multitenancy por schema no PostgreSQL

## Modelo adotado

A base utiliza:

- um banco PostgreSQL
- schema `public` para dados globais
- um schema por tenant para dados tenant-aware

## Regra oficial

A aplicação **não troca conexão por tenant**.  
Ela troca o `search_path`.

Exemplo conceitual:

```sql
SET search_path TO "tenant_schema", public;
```

## Fonte oficial do tenant atual

O tenant atual da execução deve sempre ser obtido por:

- `TenantContext`

## Quem troca o schema

A troca de `search_path` deve sempre ser feita por:

- `TenantSearchPathService`

## Fluxo HTTP tenant-aware

No HTTP, o tenant é resolvido por:

- header `X-Tenant-Id`

O middleware oficial é:

- `ResolveTenantMiddleware`

Esse middleware:

1. lê o `X-Tenant-Id`
2. busca o tenant pelo `code`
3. exige tenant com `status = 'active'`
4. aplica `search_path`
5. grava o tenant no `TenantContext`
6. limpa o contexto no `finally`
7. volta o `search_path` para `public`

## Regra oficial de tenant ativo

O tenant é considerado ativo quando:

```text
status = 'active'
```

Essa regra vale para:

- HTTP
- jobs
- listeners
- commands tenant-aware

---

# 11. Execuções tenant-aware fora do HTTP

Além do fluxo HTTP, o projeto também suporta tenancy por schema em:

- jobs
- listeners
- commands

## Executor oficial

Execuções tenant-aware fora do HTTP devem usar:

- `TenantExecutionManager`

Esse serviço é responsável por:

- restaurar `TenantContext`
- aplicar `search_path`
- executar o callback
- restaurar contexto anterior, quando existir
- limpar contexto e voltar para `public` no final

## Regras obrigatórias

- jobs tenant-aware devem transportar `tenant_id`
- listeners tenant-aware devem restaurar contexto antes da regra
- commands tenant-aware devem executar via `TenantExecutionManager`
- nenhuma regra tenant-aware fora do HTTP pode manipular schema manualmente fora do executor oficial

## Exceção administrativa

Comandos estritamente administrativos, como bootstrap estrutural ou migração específica por schema, podem usar `TenantSearchPathService` diretamente, desde que isso seja tratado como exceção documentada, não como padrão de negócio.

---

# 12. Commands importantes

## `php artisan migrate`

Aplica as migrations globais da base.

## `php artisan db:seed`

Executa seeders da aplicação.

## `php artisan passport:keys`

Gera as chaves do Passport.

## `php artisan passport:client --public`

Cria client OAuth2 público para Authorization Code + PKCE.

## `php artisan passport:client --client`

Cria client OAuth2 confidencial para integrações sistema-a-sistema com Client Credentials.

## `php artisan tenant:reprocess {tenant_id?} {--all}`

Command tenant-aware para reprocessamento.

### Como funciona

#### Tenant específico

```bash
php artisan tenant:reprocess 10
```

Executa o fluxo tenant-aware apenas para o tenant informado.

#### Todos os tenants ativos

```bash
php artisan tenant:reprocess --all
```

Executa apenas para tenants com:

```text
status = 'active'
```

### Regra técnica

Esse command não deve trocar schema manualmente.  
Ele deve sempre executar usando:

- `TenantExecutionManager`

---

# 13. Logging e observabilidade

## Identificadores técnicos oficiais

A base usa:

- `request_id`
- `trace_id`

Eles são gerados ou reaproveitados por:

- `RequestContextMiddleware`

Headers correspondentes:

- `X-Request-Id`
- `X-Trace-Id`

## Logging de request

A API grava request logging persistido por:

- `ApiRequestLoggingMiddleware`
- `ApiRequestLogger`

O logger sempre preserva metadados operacionais úteis (`request_id`, `trace_id`, status, rota, duração, usuário e tenant quando houver), mas a persistência de payloads é configurável para reduzir risco de PII em produção.

Variáveis oficiais:

```env
API_REQUEST_LOGGING_ENABLED=true
API_REQUEST_LOGGING_STORE_HEADERS=true
API_REQUEST_LOGGING_STORE_QUERY=false
API_REQUEST_LOGGING_STORE_REQUEST_BODY=false
API_REQUEST_LOGGING_STORE_RESPONSE_BODY=false
API_REQUEST_LOGGING_MAX_TEXT_LENGTH=2000
```

Recomendação para produção:

- manter headers técnicos habilitados
- desabilitar query/body/response body por padrão
- habilitar body logging apenas em rotas/casos com justificativa explícita
- nunca usar logs de request como armazenamento funcional
- não persistir XML/documentos fiscais completos em logs operacionais

## Campos principais de request log

- `request_id`
- `trace_id`
- `tenant_id`
- `tenant_code`
- `user_id`
- `oauth_client_id`
- `method`
- `route`
- `uri`
- `http_status`
- `ip`
- `request_headers`
- `request_query`
- `request_body`
- `response_body`
- `processing_status`
- `duration_ms`

## Tabelas de logging

Exemplos de logs persistidos:

- `api_request_logs`
- `system_logs`
- `audit_logs`
- `authentication_logs`

## Regras de segurança dos logs

Nunca persistir em claro:

- `authorization`
- `password`
- `current_password`
- `new_password`
- `client_secret`
- tokens sensíveis
- credenciais

Os loggers da base já fazem sanitização desses campos.

Além da sanitização, a base possui limites de profundidade, quantidade de itens e tamanho de texto para evitar payloads excessivos.

## Retenção de logs

Logs em banco precisam de retenção explícita. O command oficial é:

```bash
php artisan logs:prune
```

Para simular sem apagar:

```bash
php artisan logs:prune --dry-run
```

O agendamento padrão roda diariamente em `LOG_PRUNING_SCHEDULE_TIME`.

Variáveis oficiais:

```env
LOG_PRUNING_ENABLED=true
LOG_PRUNING_SCHEDULE_TIME=02:15
LOG_RETENTION_API_REQUEST_DAYS=30
LOG_RETENTION_SYSTEM_DAYS=90
LOG_RETENTION_AUTHENTICATION_DAYS=180
LOG_RETENTION_AUDIT_DAYS=365
LOG_RETENTION_QUEUE_EXECUTION_DAYS=30
LOG_RETENTION_QUEUE_JOB_DAYS=30
LOG_RETENTION_QUEUE_WORKER_DAYS=30
```

Use valores `<= 0` apenas quando quiser desabilitar pruning de uma tabela conscientemente.

---

# 14. Padrão de testes

A suíte de testes da base cobre a infraestrutura principal do projeto.

## Áreas cobertas

- autenticação OAuth
- rotas protegidas
- resolução de tenant no HTTP
- vínculo usuário x tenant
- role e password changed
- request context (`X-Request-Id` / `X-Trace-Id`)
- request logging persistido
- login web administrativo
- autorização web por sessão e permissões próprias
- consulta administrativa de logs com mascaramento na visualização
- sanitização de payload sensível
- `TenantContext`
- `TenantExecutionManager`
- trait de job tenant-aware
- command `tenant:reprocess`
- listener tenant-aware
- cleanup de `search_path`
- exceções e logging técnico

## Organização

```text
tests/
├── Feature/
│   ├── Architecture/
│   ├── Auth/
│   ├── Console/
│   ├── Listeners/
│   ├── Observability/
│   ├── Queue/
│   ├── Tenant/
│   └── Web/
├── Support/
└── Unit/
    └── Tenant/
```

## Rodar toda a suíte

```bash
php artisan test
```

## Rodar arquivo específico

```bash
php artisan test tests/Feature/Auth/IssueTokenTest.php
```

## Rodar com filtro

```bash
php artisan test --filter=TenantResolutionTest
```

## Observação importante

Alguns testes exigem PostgreSQL real, especialmente os que validam:

- `search_path`
- schema por tenant
- cleanup após execução tenant-aware

Quando o driver não é PostgreSQL, esses testes podem ser ignorados deliberadamente.

## Proteção contra reset acidental

A suíte de testes limpa tabelas públicas e remove schemas tenant criados durante os testes. Para impedir execução destrutiva fora do banco correto, o reset exige:

```env
APP_ENV=testing
ALLOW_TEST_DATABASE_RESET=true
TEST_DATABASE_NAME=basephp_test
DB_DATABASE=basephp_test
```

Se `DB_DATABASE` não for exatamente igual a `TEST_DATABASE_NAME`, a limpeza falha antes de truncar tabelas ou dropar schemas.

## Testes de contrato arquitetural

Os testes em `tests/Feature/Architecture/BaseContractArchitectureTest.php` protegem a base contra regressões estruturais. Eles validam:

- rotas públicas, rotas client credentials, rotas de usuário e rotas admin com middlewares corretos
- scopes usados nas rotas presentes em `OAuthScopes`
- Swagger/OpenAPI sincronizado com `OAuthScopes`
- controllers de API usando `ApiResponse`
- exception handler usando o contrato de erro padrão
- controllers sem manipulação direta de tenancy
- `SET search_path` restrito à infraestrutura de tenancy
- configuração mínima de logging e retenção
- presença desta documentação de contratos no README

Se um novo projeto derivado precisar mudar algum desses pontos, ajuste o contrato deliberadamente. Não remova o teste para silenciar falha pontual.

---

# 15. Qualidade automatizada

O projeto usa PHPStan/Larastan como análise estática incremental.

## Análise estática

```bash
composer analyse
```

## Gate local de qualidade

```bash
composer quality
```

Esse comando executa:

- `composer validate --strict`
- `composer audit`
- cache/clear de configuração Laravel
- geração da documentação OpenAPI
- validação do módulo web com `composer web:check`
- teste de formatação com Pint
- PHPStan/Larastan
- suíte PHPUnit

## Formatação

```bash
composer format
```

Para apenas verificar o estilo sem alterar arquivos:

```bash
composer format:test
```

O CI executa o gate de qualidade em pushes para `main`, `develop` e em pull requests.

O workflow oficial fica em `.github/workflows/ci.yml` e executa:

1. checkout
2. setup do PHP 8.3
3. validação do Composer
4. instalação das dependências PHP
5. setup do Node 24
6. instalação das dependências web
7. build Vite/NPM
8. geração das chaves Passport
9. `composer audit`
10. cache/clear de configuração
11. geração OpenAPI
12. validação do módulo web com `composer web:check`
13. Pint
14. PHPStan/Larastan
15. PHPUnit

`composer web:check` executa:

- `php artisan web:assets:check`
- `php artisan view:cache`
- `php artisan view:clear`

O comando `web:assets:check` valida a presença dos assets oficiais do `templateweb/master` e impede `@vite` dentro das views administrativas. O build Vite/NPM continua no CI para validar os assets gerais do Laravel e evitar regressões de frontend.

---

# 16. Regras obrigatórias de desenvolvimento

## Controllers

- não contêm regra de negócio
- não manipulam schema
- não persistem logs manualmente
- controllers de API retornam respostas por `ApiResponse`
- controllers web retornam views ou redirects

## Requests

- validam entrada
- não contêm regra de negócio

## Services

- concentram regra de negócio
- usam tenancy/logging já fornecidos pela base
- consultas administrativas de logs devem passar por service específico

## Middlewares

- aplicam autenticação
- contexto técnico
- tenancy
- autorização
- logging transversal

## Jobs / listeners / commands

- devem usar `TenantExecutionManager` quando forem tenant-aware
- não devem trocar schema manualmente

## Logging

- sempre via serviços de logging
- nunca improvisado em controller/service de domínio
- payload completo de request/response deve ser exceção, não padrão de produção
- toda tabela de log precisa ter política de retenção
- visualização web de logs deve aplicar mascaramento antes de renderizar
- acesso a detalhe de log e payload detalhado no painel administrativo deve ser auditado

## Web administrativo

- usa guard `web`, sessão Laravel e CSRF
- não aceita Bearer token OAuth como autenticação do painel
- permissões ficam em `WebAdminPermissions`
- assets seguem o template `templateweb/master`
- `composer web:check` valida assets estáticos e compilação das views
- telas administrativas devem ser operacionais, compactas e baseadas em tabelas/cards funcionais
- ações sensíveis devem registrar auditoria via `AdminWebAuditService`
- login, logout e recuperação de senha administrativa devem permanecer separados do OAuth da API

## Documentação e storage

- Swagger/OpenAPI deve ficar público somente em `local`/`testing` ou atrás de allowlist/autenticação
- `L5_SWAGGER_UI_PERSIST_AUTHORIZATION=false` deve ser o padrão
- `FILESYSTEM_LOCAL_SERVE=false` deve permanecer desabilitado para não expor storage privado

---

# 17. Fluxo recomendado para desenvolvimento de novos módulos

Ao iniciar um novo módulo:

1. definir contrato da rota
2. criar Request
3. criar Service
4. criar Resource
5. aplicar middleware adequado
6. garantir comportamento tenant-aware, quando necessário
7. adicionar logs necessários
8. criar testes de feature e unitários
9. validar resposta JSON padrão
10. validar impacto em observabilidade
11. para telas web, validar sessão, permissão, view Blade e mascaramento visual

---

# 18. Estado atual da base

Esta base já fornece:

- infraestrutura de autenticação OAuth2
- módulo web administrativo Blade com login por sessão
- contrato de permissões web administrativas
- infraestrutura de multitenancy por schema
- request context
- request logging configurável
- consulta web de logs da API com mascaramento na visualização
- logging persistido
- retenção de logs operacionais
- commands tenant-aware
- proteção de Swagger/OpenAPI por ambiente/IP
- storage privado sem rota pública por padrão
- testes para infraestrutura principal
- README como documentação operacional central

Ou seja: a base já está pronta para sustentar os primeiros módulos reais do domínio com segurança, desde que os novos projetos mantenham as configurações obrigatórias deste README.

---

# 19. Próximos passos recomendados

Depois da base estabilizada, os próximos passos naturais são:

- endurecer cobertura de módulos reais
- definir contratos gRPC
- implementar o primeiro módulo de negócio em cima da infraestrutura já criada
- revisar periodicamente `composer audit`, escopos OAuth e políticas de retenção

---

# 20. Observação final

Esta base deve ser tratada como **contrato arquitetural do projeto**.

Mudanças estruturais relevantes em:

- autenticação
- tenancy
- logging
- execução fora do HTTP
- formato de resposta
- estratégia de testes
- módulo web administrativo

devem ser feitas com revisão arquitetural explícita, e não por conveniência pontual.
