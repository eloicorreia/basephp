<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class TenantProvisionCommand extends Command
{
    protected $signature = 'tenant:provision
                            {code : Código do tenant}
                            {name : Nome do tenant}
                            {schema_name : Nome do schema}
                            {--force : Força execução em produção}';

    protected $description = 'Cria um novo tenant, schema, migrations e seeders.';

    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $tenant = $this->tenantProvisioningService->provision(
                code: (string) $this->argument('code'),
                name: (string) $this->argument('name'),
                schemaName: (string) $this->argument('schema_name'),
                force: (bool) $this->option('force'),
            );

            $this->info(sprintf(
                'Tenant [%s] provisionado com sucesso no schema [%s].',
                $tenant->code,
                $tenant->schema_name
            ));

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
    }
}