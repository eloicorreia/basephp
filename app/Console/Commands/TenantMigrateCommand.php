<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantSearchPathService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate
                            {--tenant= : Código do tenant cadastrado em public.tenants}
                            {--schema= : Nome do schema do tenant}
                            {--force : Força a execução mesmo em ambiente de produção}';

    protected $description = 'Executa as migrations do tenant no schema informado.';

    public function __construct(
        private readonly TenantSearchPathService $tenantSearchPathService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantCode = $this->option('tenant');
        $schemaName = $this->option('schema');
        $force = (bool) $this->option('force');

        if (($tenantCode === null || $tenantCode === '') && ($schemaName === null || $schemaName === '')) {
            $this->error('Informe --tenant ou --schema.');
            return self::FAILURE;
        }

        if (($tenantCode !== null && $tenantCode !== '') && ($schemaName !== null && $schemaName !== '')) {
            $this->error('Use apenas uma opção: --tenant ou --schema.');
            return self::FAILURE;
        }

        try {
            $resolvedSchema = $this->resolveSchemaName($tenantCode, $schemaName);

            $this->info(sprintf('Aplicando migrations no schema [%s]...', $resolvedSchema));

            $this->tenantSearchPathService->setTenantSchema($resolvedSchema);

            $exitCode = Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => $force,
            ]);

            $this->line(Artisan::output());

            if ($exitCode !== 0) {
                $this->logPersistenceService->logSystemWarning(
                    message: 'Falha ao executar migrations do tenant.',
                    category: 'tenant',
                    operation: 'tenant_migrate',
                    userId: null,
                    context: [
                        'schema_name' => $resolvedSchema,
                        'exit_code' => $exitCode,
                    ],
                    processingStatus: 'error',
                );

                $this->error(sprintf(
                    'Falha ao executar migrations do schema [%s].',
                    $resolvedSchema
                ));

                return self::FAILURE;
            }

            $this->logPersistenceService->logSystemInfo(
                message: 'Migrations do tenant executadas com sucesso.',
                category: 'tenant',
                operation: 'tenant_migrate',
                userId: null,
                context: [
                    'schema_name' => $resolvedSchema,
                ],
                processingStatus: 'success',
            );

            $this->info(sprintf(
                'Migrations executadas com sucesso no schema [%s].',
                $resolvedSchema
            ));

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $this->logPersistenceService->logSystemError(
                throwable: $throwable,
                category: 'tenant',
                operation: 'tenant_migrate',
                userId: null,
                httpStatus: 500,
            );

            $this->error($throwable->getMessage());

            return self::FAILURE;
        } finally {
            try {
                $this->tenantSearchPathService->resetToPublic();
            } catch (Throwable) {
            }
        }
    }

    private function resolveSchemaName(?string $tenantCode, ?string $schemaName): string
    {
        if ($tenantCode !== null && $tenantCode !== '') {
            $tenant = Tenant::query()
                ->where('code', $tenantCode)
                ->where('status', 'active')
                ->first();

            if ($tenant === null) {
                throw new \RuntimeException('Tenant não encontrado ou inativo.');
            }

            return $tenant->schema_name;
        }

        $validatedSchemaName = (string) $schemaName;
        $this->assertValidSchemaName($validatedSchemaName);

        return $validatedSchemaName;
    }

    private function assertValidSchemaName(string $schemaName): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]{2,62}$/', $schemaName)) {
            throw new \InvalidArgumentException('Nome de schema inválido.');
        }
    }
}