# Seeders do bootstrap inicial

Este projeto mantém seeders separados por responsabilidade para que o bootstrap inicial seja previsível, seguro e idempotente em desenvolvimento, homologação e produção.

## Ordem oficial

O `DatabaseSeeder` executa os seeders nesta ordem:

1. `PermissionSeeder`
2. `RoleSeeder`
3. `AdminUserSeeder`
4. `TenantSeeder`
5. `AdminMenuSeeder`

Essa ordem é obrigatória porque roles dependem das permissões oficiais, o usuário administrador depende da role `admin`, e o menu administrativo depende das permissões usadas pelos itens do menu.

## Finalidade de cada seeder

### PermissionSeeder

Sincroniza as permissões oficiais a partir de `App\Support\Auth\PermissionRegistry::definitions()`.

Regras principais:

- cria permissões ausentes por `code`;
- atualiza metadados oficiais das permissões do registry;
- mantém `active=true` para permissões oficiais;
- não remove permissões customizadas;
- não usa `truncate` nem delete massivo.

### RoleSeeder

Cria as roles base usando `App\Enums\RoleCode` como contrato oficial:

- `admin`, com permissão `admin.full`;
- `empresa`, preservando o código já existente no projeto;
- `usuario`, preservando o código já existente no projeto.

A vinculação de permissões usa `syncWithoutDetaching`, preservando permissões customizadas já vinculadas.

> Importante: o bootstrap não deve criar a role `user`, porque o contrato atual do projeto usa `RoleCode::USUARIO` com o código `usuario`.

### AdminUserSeeder

Garante a existência do usuário administrador inicial sem duplicidade.

Variáveis usadas:

```env
ADMIN_USER_NAME="Administrador"
ADMIN_USER_EMAIL="admin@example.com"
ADMIN_USER_PASSWORD=
```

Regras principais:

- não usa factory com e-mail fixo;
- não usa `test@example.com`;
- não sobrescreve senha de usuário existente;
- não altera status de usuário existente;
- se o usuário existir sem role, vincula a role `admin`;
- se o usuário existir com outra role, não sobrescreve automaticamente.

Em `production`, se o usuário ainda não existir, `ADMIN_USER_PASSWORD` deve estar definido. Em `local` e `testing`, quando a senha não for definida, é usado fallback temporário com `must_change_password=true`.

### TenantSeeder

Cria um tenant de desenvolvimento sem criar schema e sem rodar migrations tenant.

Tenant padrão:

```text
code: tenant-dev-001
name: Tenant Desenvolvimento 001
schema_name: tenant_dev_001
status: active
```

Variáveis de controle:

```env
SEED_DEVELOPMENT_TENANT=true
DEVELOPMENT_TENANT_CODE="tenant-dev-001"
DEVELOPMENT_TENANT_NAME="Tenant Desenvolvimento 001"
DEVELOPMENT_TENANT_SCHEMA_NAME="tenant_dev_001"
```

Regra de segurança:

- em `local` e `testing`, o tenant de desenvolvimento é criado quando `SEED_DEVELOPMENT_TENANT` estiver ausente ou diferente de `false`;
- em `production`, o tenant de desenvolvimento só é criado quando `SEED_DEVELOPMENT_TENANT=true` estiver explicitamente configurado;
- se `SEED_DEVELOPMENT_TENANT=false`, o tenant é ignorado mesmo em `local/testing`.

O provisionamento de schema e as migrations tenant continuam sendo responsabilidade dos comandos próprios de tenant, como `tenants:provision` e `tenants:migrate`.

### AdminMenuSeeder

Mantém o menu dinâmico administrativo.

Regras principais:

- não retorna menu fixo para Blade;
- não remove menus customizados;
- não duplica grupos ou itens;
- incrementa `admin_menu_versions` apenas quando há mudança real;
- registra warning quando uma permissão obrigatória do menu não existe.

### Seeders públicos

O projeto também possui seeders em `Database\Seeders\Public` usados pelo bootstrap de testes e/ou fluxos públicos.

Eles devem seguir a mesma regra de preservação:

- não usar `sync()` em permissões de roles quando isso puder remover permissões customizadas;
- usar `syncWithoutDetaching()` para preservar permissões já vinculadas.

## Como executar

Rodar todos os seeders:

```bash
php artisan db:seed
```

Rodar somente o menu administrativo:

```bash
php artisan db:seed --class=AdminMenuSeeder
```

Rodar somente permissões:

```bash
php artisan db:seed --class=PermissionSeeder
```

Rodar somente roles:

```bash
php artisan db:seed --class=RoleSeeder
```

## O que o seed não faz

`php artisan db:seed` não provisiona schemas tenant e não roda migrations tenant. Esses fluxos devem permanecer separados para evitar efeitos colaterais em produção.

## Validações recomendadas

Após executar `db:seed` mais de uma vez, as consultas abaixo devem retornar vazio:

```sql
select email, count(*)
from users
group by email
having count(*) > 1;

select code, count(*)
from permissions
group by code
having count(*) > 1;

select code, count(*)
from roles
group by code
having count(*) > 1;

select code, count(*)
from admin_menu_items
group by code
having count(*) > 1;
```

Validação para confirmar que a role fora do contrato não foi criada:

```sql
select *
from roles
where code = 'user';
```

Essa consulta deve retornar vazio, porque o código correto no projeto é `usuario`.
