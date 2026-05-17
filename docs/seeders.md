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

Cria as roles base:

- `admin`, com permissão `admin.full`;
- `user`, como role operacional base sem permissões adicionais obrigatórias neste bootstrap.

A vinculação de permissões usa `syncWithoutDetaching`, preservando permissões customizadas já vinculadas.

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

Cria um tenant de desenvolvimento somente para bootstrap local/testing, sem criar schema e sem rodar migrations tenant.

Tenant padrão:

```text
code: tenant-dev-001
name: Tenant Desenvolvimento 001
schema_name: tenant_dev_001
status: active
```

Variável de controle:

```env
SEED_DEVELOPMENT_TENANT=true
```

Em produção, o seeder só cria esse tenant se houver permissão explícita pela configuração. O provisionamento de schema e as migrations tenant continuam sendo responsabilidade dos comandos próprios de tenant, como `tenants:provision` e `tenants:migrate`.

### AdminMenuSeeder

Mantém o menu dinâmico administrativo.

Regras principais:

- não retorna menu fixo para Blade;
- não remove menus customizados;
- não duplica grupos ou itens;
- incrementa `admin_menu_versions` apenas quando há mudança real.

## Como executar

Rodar todos os seeders:

```bash
php artisan db:seed
```

Rodar somente o menu administrativo:

```bash
php artisan db:seed --class=AdminMenuSeeder
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
