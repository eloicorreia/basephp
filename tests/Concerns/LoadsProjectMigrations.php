<?php

declare(strict_types=1);

namespace Tests\Concerns;

trait LoadsProjectMigrations
{
    protected static bool $projectMigrationsLoaded = false;

    protected function loadProjectMigrations(): void
    {
        if (self::$projectMigrationsLoaded) {
            return;
        }

        $this->artisan('migrate:fresh', [
            '--database' => config('database.default'),
            '--path' => 'database/migrations',
        ]);

        $this->artisan('migrate', [
            '--database' => config('database.default'),
            '--path' => database_path('migrations/public'),
            '--realpath' => true,
        ]);

        $this->artisan('migrate', [
            '--database' => config('database.default'),
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
        ]);

        self::$projectMigrationsLoaded = true;
    }
}