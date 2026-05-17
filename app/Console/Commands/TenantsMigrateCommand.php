<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DTOs\Tenant\TenantProvisioningResult;
use App\Models\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final class TenantsMigrateCommand extends Command
{
    protected $signature = 'tenants:migrate
        {--tenant= : Código do tenant específico}
        {--force : Executar migrations em modo force}
        {--only-active : Migrar apenas tenants ativos}';

    protected $description = 'Executa migrations dos schemas tenant sem depender de Tinker.';

    public function __construct(
        private readonly TenantProvisioningService $tenantProvisioningService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenants = $this->targetTenants();

        if ($tenants === null) {
            return self::FAILURE;
        }

        if ($tenants->isEmpty()) {
            $this->warn('Nenhum tenant encontrado para migração.');

            return self::SUCCESS;
        }

        $hasFailure = false;

        foreach ($tenants as $tenant) {
            $this->line(sprintf(
                'Migrating tenant: %s [%s]',
                (string) $tenant->code,
                (string) $tenant->schema_name,
            ));

            try {
                $result = $this->tenantProvisioningService->migrate(
                    tenant: $tenant,
                    force: (bool) $this->option('force'),
                );

                $this->renderResult($result);
                $hasFailure = $hasFailure || $result->failed();
            } catch (Throwable $throwable) {
                $hasFailure = true;
                $this->error('FAILED: '.$throwable->getMessage());
            }
        }

        return $hasFailure ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return Collection<int, Tenant>|null
     */
    private function targetTenants(): ?Collection
    {
        $tenantCode = $this->option('tenant');

        if (is_string($tenantCode) && $tenantCode !== '') {
            $tenant = Tenant::query()
                ->where('code', $tenantCode)
                ->first();

            if (! $tenant instanceof Tenant) {
                $this->error(sprintf('Tenant não encontrado: %s', $tenantCode));

                return null;
            }

            return new Collection([$tenant]);
        }

        $query = Tenant::query()->orderBy('code');

        if ((bool) $this->option('only-active')) {
            $query->where('status', Tenant::STATUS_ACTIVE);
        }

        return $query->get();
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
