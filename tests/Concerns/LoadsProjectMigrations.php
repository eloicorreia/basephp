<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Support\Facades\Schema;
use RuntimeException;

trait LoadsProjectMigrations
{
    protected function loadProjectMigrations(): void
    {
        if ($this->projectSchemaIsReady()) {
            return;
        }

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

        if (! $this->projectSchemaIsReady()) {
            throw new RuntimeException(
                'O schema de testes não ficou consistente após o bootstrap das migrations.'
            );
        }
    }

    protected function projectSchemaIsReady(): bool
    {
        return Schema::hasTable('tenants')
            && Schema::hasTable('api_request_logs')
            && Schema::hasColumn('users', 'role_id')
            && Schema::hasColumn('users', 'is_active')
            && Schema::hasColumn('users', 'must_change_password');
    }
}