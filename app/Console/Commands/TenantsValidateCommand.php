<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DTOs\Tenant\TenantProvisioningResult;
use App\Models\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final class TenantsValidateCommand extends Command
{
    protected $signature = 'tenants:validate
        {--tenant= : Código do tenant específico}
        {--only-active : Validar apenas tenants ativos}';

    protected $description = 'Valida schemas tenant e tabelas obrigatórias.';

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
            $this->warn('Nenhum tenant encontrado para validação.');

            return self::SUCCESS;
        }

        $hasFailure = false;
        $rows = [];

        foreach ($tenants as $tenant) {
            try {
                $result = $this->tenantProvisioningService->validate($tenant);
            } catch (Throwable $throwable) {
                $result = TenantProvisioningResult::make(
                    tenantCode: (string) $tenant->code,
                    schemaName: (string) $tenant->schema_name,
                    success: false,
                    schemaExists: false,
                    migrated: false,
                    errors: [$throwable->getMessage()],
                );
            }

            $hasFailure = $hasFailure || $result->failed();

            $rows[] = [
                $result->tenantCode,
                $result->schemaName,
                $result->schemaExists ? 'yes' : 'no',
                $result->missingTables === [] ? '-' : implode(', ', $result->missingTables),
                $result->success ? 'OK' : 'FAILED',
            ];
        }

        $this->table(
            ['Tenant', 'Schema', 'Schema exists', 'Missing tables', 'Status'],
            $rows,
        );

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
}
