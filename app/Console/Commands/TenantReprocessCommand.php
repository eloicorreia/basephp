<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use Illuminate\Console\Command;

final class TenantReprocessCommand extends Command
{
    protected $signature = 'tenant:reprocess {tenant_id?} {--all}';

    protected $description = 'Reprocessa dados de um tenant específico ou de todos os tenants ativos.';

    public function handle(TenantExecutionManager $executionManager): int
    {
        if ((bool) $this->option('all')) {
            Tenant::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->each(function (Tenant $tenant) use ($executionManager): void {
                    $executionManager->run($tenant, function () use ($tenant): void {
                        $this->info("Processando tenant {$tenant->id} no schema {$tenant->schema_name}");
                    });
                });

            return self::SUCCESS;
        }

        $tenantId = $this->argument('tenant_id');

        if ($tenantId === null) {
            $this->error('Informe {tenant_id} ou utilize --all.');
            return self::FAILURE;
        }

        $tenant = Tenant::query()->findOrFail($tenantId);

        $executionManager->run($tenant, function () use ($tenant): void {
            $this->info("Processando tenant {$tenant->id} no schema {$tenant->schema_name}");
        });

        return self::SUCCESS;
    }
}