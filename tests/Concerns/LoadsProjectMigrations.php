<?php

declare(strict_types=1);

namespace Tests\Concerns;

trait LoadsProjectMigrations
{
    protected static bool $projectDatabasePrepared = false;

    protected function loadProjectMigrations(): void
    {
        if (self::$projectDatabasePrepared) {
            return;
        }

        $this->artisan('migrate:fresh', [
            '--database' => config('database.default'),
            '--force' => true,
        ])->run();

        $this->artisan('migrate', [
            '--database' => config('database.default'),
            '--path' => database_path('migrations/public'),
            '--realpath' => true,
            '--force' => true,
        ])->run();

        self::$projectDatabasePrepared = true;
    }
}