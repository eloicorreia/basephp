<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class TenantSeederService
{
    public function runTenantSeeders(bool $force = false): void
    {
        $exitCode = Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\Tenant\\TenantDatabaseSeeder',
            '--force' => $force,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Falha ao executar seeders do tenant.');
        }
    }
}