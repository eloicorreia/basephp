# BasePHP

Base arquitetural para APIs backend-only em **PHP 8.3 + Laravel 12 + PostgreSQL**, preparada para:

- APIs REST versionadas
- autenticação OAuth2 com Laravel Passport
- multitenancy por **schema no PostgreSQL**
- logging persistido em banco
- observabilidade com `request_id` e `trace_id`
- execução tenant-aware no HTTP e fora da requisição web
- testes de infraestrutura e regressão

---

# 1. Objetivo do projeto

Este projeto é uma base técnica para construção de aplicações **backend-only**, sem frontend obrigatório, orientadas à exposição de APIs e serviços para consumo por outras aplicações.

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
- **gRPC / protobuf** preparado na base
- **PHPUnit** para testes

## Banco de dados

- banco oficial: **PostgreSQL**
- schema global padrão: `public`
- um schema por tenant
- `search_path` controlado pela aplicação

## Estilo de aplicação

- backend-only
- APIs REST versionadas
- autenticação OAuth2
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
│   ├── Logging/
│   └── Tenant/
├── Support/
│   └── Tenant/
bootstrap/
config/
database/
├── migrations/
│   ├── public/
│   └── tenant/
docs/
└── architecture/
routes/
tests/
```

## Documentação arquitetural

A pasta `docs/architecture` contém os contratos oficiais da base.

Arquivos principais:

- `authentication-and-tenancy-contract.md`
- `logging-and-observability-contract.md`

Esses documentos devem ser tratados como referência arquitetural obrigatória do projeto.

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

---

# 6. Autenticação OAuth2 com Passport

## Guard oficial

O guard oficial da API é:

- `api`

Ele utiliza:

- driver `passport`

## Fluxo oficial atual

A base está preparada para autenticação via OAuth2 com Laravel Passport.

Atualmente, o fluxo principal suportado é o **password grant** para first-party clients, respeitando a arquitetura atual do projeto.

## Quando usar

### Password grant
Use quando a aplicação cliente é controlada pela própria plataforma e precisa autenticar usuário + senha.

### Client credentials
Pode ser utilizado futuramente para integração sistema-a-sistema, quando não houver usuário humano autenticado.

---

# 7. Como instalar e subir localmente

## 7.1. Clonar o projeto

```bash
git clone <url-do-repositorio>
cd basephp
```

## 7.2. Instalar dependências

```bash
composer install
```

## 7.3. Criar `.env`

```bash
cp .env.example .env
```

## 7.4. Gerar chave da aplicação

```bash
php artisan key:generate
```

## 7.5. Configurar banco PostgreSQL

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
```

## 7.6. Rodar migrations

```bash
php artisan migrate
```

## 7.7. Instalar chaves do Passport

Se for a primeira execução do ambiente, gere as chaves do Passport:

```bash
php artisan passport:keys
```

Se precisar recriar:

```bash
php artisan passport:keys --force
```

## 7.8. Criar client OAuth password grant

```bash
php artisan passport:client --password
```

Esse command vai solicitar:

- nome do client
- provider
- redirect

Para ambiente local, normalmente:

- nome: `Local Password Client`
- provider: `users`
- redirect: `http://localhost`

Ao final, ele retorna:

- `client_id`
- `client_secret`

Esses dados são usados no endpoint `/oauth/token`.

## 7.9. Seeders

Se o projeto exigir dados base:

```bash
php artisan db:seed
```

Ou seed específico:

```bash
php artisan db:seed --class=TenantSeeder
```

## 7.10. Subir servidor local

```bash
php artisan serve
```

---

# 8. Como autenticar e gerar token OAuth

## Endpoint

```text
POST /oauth/token
```

## Exemplo de payload

```json
{
  "grant_type": "password",
  "client_id": "SEU_CLIENT_ID",
  "client_secret": "SEU_CLIENT_SECRET",
  "username": "usuario@local.test",
  "password": "senha-do-usuario",
  "scope": "user.profile tenant.access"
}
```

## Exemplo com curl

```bash
curl --request POST \
  --url http://localhost:8000/oauth/token \
  --header 'Content-Type: application/json' \
  --data '{
    "grant_type": "password",
    "client_id": "SEU_CLIENT_ID",
    "client_secret": "SEU_CLIENT_SECRET",
    "username": "usuario@local.test",
    "password": "senha-do-usuario",
    "scope": "user.profile tenant.access"
  }'
```

## Resposta esperada

```json
{
  "token_type": "Bearer",
  "expires_in": 31536000,
  "access_token": "...",
  "refresh_token": "..."
}
```

## Como consumir rota protegida

Depois de obter o token:

```bash
curl --request GET \
  --url http://localhost:8000/api/v1/auth/me \
  --header 'Authorization: Bearer SEU_ACCESS_TOKEN' \
  --header 'X-Tenant-Id: tenant-main'
```

---

# 9. Multitenancy por schema no PostgreSQL

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

# 10. Execuções tenant-aware fora do HTTP

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

# 11. Commands importantes

## `php artisan migrate`

Aplica as migrations globais da base.

## `php artisan db:seed`

Executa seeders da aplicação.

## `php artisan passport:keys`

Gera as chaves do Passport.

## `php artisan passport:client --password`

Cria client OAuth2 do tipo password grant.

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

# 12. Logging e observabilidade

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

---

# 13. Padrão de testes

A suíte de testes da base cobre a infraestrutura principal do projeto.

## Áreas cobertas

- autenticação OAuth
- rotas protegidas
- resolução de tenant no HTTP
- vínculo usuário x tenant
- role e password changed
- request context (`X-Request-Id` / `X-Trace-Id`)
- request logging persistido
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
│   ├── Auth/
│   ├── Console/
│   ├── Listeners/
│   ├── Observability/
│   ├── Queue/
│   └── Tenant/
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

---

# 14. Regras obrigatórias de desenvolvimento

## Controllers

- não contêm regra de negócio
- não manipulam schema
- não persistem logs manualmente

## Requests

- validam entrada
- não contêm regra de negócio

## Services

- concentram regra de negócio
- usam tenancy/logging já fornecidos pela base

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

---

# 15. Fluxo recomendado para desenvolvimento de novos módulos

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

---

# 16. Estado atual da base

Esta base já fornece:

- infraestrutura de autenticação OAuth2
- infraestrutura de multitenancy por schema
- request context
- request logging
- logging persistido
- commands tenant-aware
- testes para infraestrutura principal
- documentação arquitetural oficial

Ou seja: a base já está pronta para sustentar os primeiros módulos reais do domínio com segurança.

---

# 17. Próximos passos recomendados

Depois da base estabilizada, os próximos passos naturais são:

- colocar a suíte em CI
- endurecer cobertura de módulos reais
- definir contratos gRPC
- implementar o primeiro módulo de negócio em cima da infraestrutura já criada

---

# 18. Observação final

Esta base deve ser tratada como **contrato arquitetural do projeto**.

Mudanças estruturais relevantes em:

- autenticação
- tenancy
- logging
- execução fora do HTTP
- formato de resposta
- estratégia de testes

devem ser feitas com revisão arquitetural explícita, e não por conveniência pontual.
