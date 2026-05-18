<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\Public\PermissionSeeder;
use Database\Seeders\Public\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

trait LoadsProjectMigrations
{
    protected function loadProjectMigrations(): void
    {
        if (! $this->projectSchemaIsReady()) {
            $this->bootstrapProjectMigrations();
        }

        $this->resetDatabaseState();
    }

    private function bootstrapProjectMigrations(): void
    {
        $database = (string) config('database.default');

        $freshExitCode = $this->artisan('migrate:fresh', [
            '--database' => $database,
            '--force' => true,
        ])->run();

        if ($freshExitCode !== 0) {
            throw new RuntimeException('Falha ao executar migrate:fresh no ambiente de testes.');
        }

        $publicExitCode = $this->artisan('migrate', [
            '--database' => $database,
            '--path' => database_path('migrations/public'),
            '--realpath' => true,
            '--force' => true,
        ])->run();

        if ($publicExitCode !== 0) {
            throw new RuntimeException('Falha ao executar as migrations públicas no ambiente de testes.');
        }

        $tenantExitCode = $this->artisan('migrate', [
            '--database' => $database,
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ])->run();

        if ($tenantExitCode !== 0) {
            throw new RuntimeException('Falha ao executar as migrations tenant no ambiente de testes.');
        }

        if (! $this->projectSchemaIsReady()) {
            throw new RuntimeException(
                'O schema de testes não ficou consistente após o bootstrap das migrations.'
            );
        }
    }

    private function resetDatabaseState(): void
    {
        $this->ensureTestingDatabase();

        DB::statement('SET search_path TO public');

        $this->dropNonSystemSchemas();

        $tables = $this->publicTablesForCleanup();

        if ($tables === []) {
            return;
        }

        $qualifiedTables = array_map(
            fn (string $table): string => $this->quoteIdentifier($table),
            $tables
        );

        DB::statement(sprintf(
            'TRUNCATE TABLE %s RESTART IDENTITY CASCADE',
            implode(', ', $qualifiedTables)
        ));

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(AdminMenuSeeder::class);
    }

    private function ensureTestingDatabase(): void
    {
        $database = config(sprintf('database.connections.%s.database', (string) config('database.default')));
        $expectedDatabase = config('database.testing_reset.database');
        $resetEnabled = (bool) config('database.testing_reset.enabled', false);

        if (
            ! app()->environment('testing')
            || ! $resetEnabled
            || ! is_string($database)
            || ! is_string($expectedDatabase)
            || $database !== $expectedDatabase
        ) {
            throw new RuntimeException('A limpeza automática do banco só pode ser executada em ambiente de testes.');
        }
    }

    private function dropNonSystemSchemas(): void
    {
        /** @var array<int, object{schema_name: string}> $rows */
        $rows = DB::select(
            "SELECT schema_name
            FROM information_schema.schemata
            WHERE schema_name <> 'public'
            AND schema_name <> 'information_schema'
            AND schema_name NOT LIKE 'pg_%'
            ORDER BY schema_name"
        );

        foreach ($rows as $row) {
            DB::statement(sprintf(
                'DROP SCHEMA IF EXISTS %s CASCADE',
                $this->quoteIdentifier((string) $row->schema_name)
            ));
        }
    }

    /**
     * @return array<int, string>
     */
    private function publicTablesForCleanup(): array
    {
        /** @var array<int, object{tablename: string}> $rows */
        $rows = DB::select(
            "SELECT tablename
            FROM pg_tables
            WHERE schemaname = 'public'
            AND tablename <> 'migrations'
            ORDER BY tablename"
        );

        return array_map(
            static fn (object $row): string => (string) $row->tablename,
            $rows
        );
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    protected function projectSchemaIsReady(): bool
    {
        return Schema::hasTable('tenants')
            && Schema::hasTable('permissions')
            && Schema::hasTable('role_permissions')
            && Schema::hasTable('admin_menu_groups')
            && Schema::hasTable('admin_menu_items')
            && Schema::hasTable('admin_menu_item_permissions')
            && Schema::hasTable('admin_menu_versions')
            && Schema::hasTable('admin_menu_event_logs')
            && Schema::hasTable('tenant_provisioning_runs')
            && Schema::hasTable('tenant_user_security_states')
            && Schema::hasColumn('permissions', 'group')
            && Schema::hasColumn('permissions', 'is_system')
            && Schema::hasColumn('role_permissions', 'assigned_by')
            && Schema::hasColumn('role_permissions', 'assigned_at')
            && Schema::hasTable('api_request_logs')
            && Schema::hasTable('tenant_password_policies')
            && Schema::hasTable('user_password_histories')
            && Schema::hasTable('tenant_system_settings')
            && Schema::hasTable('tenant_security_settings')
            && Schema::hasTable('tenant_api_settings')
            && Schema::hasTable('tenant_queue_settings')
            && Schema::hasTable('tenant_audit_settings')
            && Schema::hasTable('tenant_integration_settings')
            && Schema::hasTable('tenant_webhook_settings')
            && Schema::hasTable('tenant_notification_settings')
            && Schema::hasColumn('users', 'role_id')
            && Schema::hasColumn('users', 'is_active')
            && Schema::hasColumn('users', 'must_change_password')
            && Schema::hasColumn('users', 'password_changed_at');
    }
}
