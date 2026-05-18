# Operações de Tenants

## 1. Diferença entre migrations públicas e migrations tenant

O projeto utiliza PostgreSQL com multi-tenancy por schema.

As migrations públicas são executadas no schema `public` e mantêm estruturas globais, como usuários, tenants, permissões, menus administrativos, logs técnicos e tabelas compartilhadas.

As migrations tenant ficam em:

```bash
database/migrations/tenant
```

Essas migrations representam estruturas que devem existir dentro de cada schema de tenant.

Importante: `php artisan migrate` não executa automaticamente `database/migrations/tenant` em todos os schemas tenant. Ele executa as migrations no contexto padrão da conexão atual.

## 2. Quando usar `php artisan migrate`

Use para atualizar o schema público da aplicação:

```bash
php artisan migrate --force
```

Esse comando deve ser executado em deploys para garantir que as tabelas públicas estejam atualizadas.

## 3. Quando usar `php artisan tenants:migrate`

Use para aplicar as migrations tenant dentro dos schemas dos tenants:

```bash
php artisan tenants:migrate --force --only-active
```

Para migrar um tenant específico:

```bash
php artisan tenants:migrate --tenant=tenant-dev-001 --force
```

Esse comando valida o schema, verifica se ele existe e executa `TenantMigrationService::runTenantMigrations()`.

## 4. Como provisionar tenant novo

Para provisionar um tenant novo cujo registro já exista na tabela `tenants`:

```bash
php artisan tenants:provision --tenant=tenant-dev-001 --create-schema --force
```

O provisionamento:

1. valida `schema_name`;
2. cria o schema quando `--create-schema` for informado;
3. garante a tabela `migrations` no schema tenant;
4. executa as migrations tenant;
5. valida as tabelas obrigatórias.

O comando não ativa tenant automaticamente. Ativação deve continuar sendo uma regra explícita do domínio.

## 5. Como validar tenants

Para validar tenants ativos:

```bash
php artisan tenants:validate --only-active
```

Para validar um tenant específico:

```bash
php artisan tenants:validate --tenant=tenant-dev-001
```

A validação verifica:

- se o schema existe;
- se todas as tabelas obrigatórias existem.

A lista oficial fica centralizada em:

```bash
app/Support/Tenant/TenantRequiredTables.php
```

## 6. Como corrigir tenant incompleto

Se `tenants:validate` indicar tabelas ausentes:

1. confirme se o `schema_name` está correto;
2. confirme se o schema existe;
3. execute:

```bash
php artisan tenants:migrate --tenant=tenant-dev-001 --force
```

4. valide novamente:

```bash
php artisan tenants:validate --tenant=tenant-dev-001
```

Se o schema não existir e o tenant for novo:

```bash
php artisan tenants:provision --tenant=tenant-dev-001 --create-schema --force
```

## 7. Ordem recomendada de deploy

```bash
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=AdminMenuSeeder --force
php artisan tenants:migrate --force --only-active
php artisan tenants:validate --only-active
php artisan optimize:clear
```

Ou usando o comando consolidado:

```bash
php artisan system:update --force --only-active
```

## 8. Logs operacionais

Falhas em comandos operacionais são registradas com categoria:

```text
tenant-operations
```

Operações:

- `tenants_migrate`
- `tenants_validate`
- `tenants_provision`
- `system_update`

Os logs incluem contexto seguro:

- `tenant_id`;
- `tenant_code`;
- `schema_name`;
- `command`;
- `error_class`;
- mensagem resumida;
- tabelas ausentes quando aplicável.

Segredos, tokens, senhas e credenciais não devem ser enviados ao contexto de log.
