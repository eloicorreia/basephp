<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class TenantMigrationService
{
    public function runTenantMigrations(bool $force = false): void
    {
        $exitCode = Artisan::call('migrate', [
            '--path' => 'database/migrations/tenant',
            '--force' => $force,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Falha ao executar migrations do tenant.');
        }
    }
}