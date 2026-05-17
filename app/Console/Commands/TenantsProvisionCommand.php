<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DTOs\Tenant\TenantProvisioningResult;
use App\Models\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Console\Command;
use Throwable;

final class TenantsProvisionCommand extends Command
{
    protected $signature = 'tenants:provision
        {--tenant= : Código do tenant específico}
        {--create-schema : Criar schema se não existir}
        {--force : Executar migrations em modo force}';

    protected $description = 'Provisiona um tenant criando schema, executando migrations e validando tabelas obrigatórias.';

    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantCode = $this->option('tenant');

        if (! is_string($tenantCode) || $tenantCode === '') {
            $this->error('A opção --tenant é obrigatória para provisionamento.');

            return self::FAILURE;
        }

        $tenant = Tenant::query()
            ->where('code', $tenantCode)
            ->first();

        if (! $tenant instanceof Tenant) {
            $this->error(sprintf('Tenant não encontrado: %s', $tenantCode));

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Provisioning tenant: %s [%s]',
            (string) $tenant->code,
            (string) $tenant->schema_name,
        ));

        try {
            $result = $this->tenantProvisioningService->provision(
                tenant: $tenant,
                createSchema: (bool) $this->option('create-schema'),
                force: (bool) $this->option('force'),
            );
        } catch (Throwable $throwable) {
            $this->error('FAILED: '.$throwable->getMessage());

            return self::FAILURE;
        }

        $this->renderResult($result);

        return $result->success ? self::SUCCESS : self::FAILURE;
    }

    private function renderResult(TenantProvisioningResult $result): void
    {
        if ($result->success) {
            $this->info('OK');

            return;
        }

        foreach ($result->errors as $error) {
            $this->error('FAILED: '.$error);
        }

        if ($result->missingTables !== []) {
            $this->warn('Missing tables: '.implode(', ', $result->missingTables));
        }
    }
}
