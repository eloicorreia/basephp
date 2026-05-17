<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\DTOs\Tenant\TenantProvisioningResult;
use App\Models\Tenant;
use App\Services\Logging\LogPersistenceService;
use App\Support\Tenant\TenantRequiredTables;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class TenantProvisioningService
{
    public function __construct(
        private TenantSchemaService $tenantSchemaService,
        private TenantMigrationService $tenantMigrationService,
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function provision(Tenant $tenant, bool $createSchema = false, bool $force = false): TenantProvisioningResult
    {
        $schemaName = (string) $tenant->schema_name;

        try {
            $this->tenantSchemaService->assertValidSchemaName($schemaName);

            $schemaExists = $this->tenantSchemaService->schemaExists($schemaName);

            if (! $schemaExists && ! $createSchema) {
                return $this->fail(
                    tenant: $tenant,
                    schemaName: $schemaName,
                    schemaExists: false,
                    migrated: false,
                    message: 'Schema não existe. Informe --create-schema para criar o schema antes das migrations.',
                    operation: 'tenants_provision',
                );
            }

            if (! $schemaExists && $createSchema) {
                $this->tenantSchemaService->createSchema($schemaName);
                $schemaExists = true;
            }

            $migrationResult = $this->migrate($tenant, $force);

            if ($migrationResult->failed()) {
                return $migrationResult;
            }

            return $this->validate($tenant);
        } catch (Throwable $throwable) {
            $this->logUnexpectedFailure($throwable, $tenant, $schemaName, 'tenants_provision');

            throw $throwable;
        }
    }

    public function validate(Tenant $tenant): TenantProvisioningResult
    {
        $schemaName = (string) $tenant->schema_name;

        try {
            $this->tenantSchemaService->assertValidSchemaName($schemaName);

            $schemaExists = $this->tenantSchemaService->schemaExists($schemaName);

            if (! $schemaExists) {
                return $this->fail(
                    tenant: $tenant,
                    schemaName: $schemaName,
                    schemaExists: false,
                    migrated: false,
                    message: 'Schema não existe.',
                    operation: 'tenants_validate',
                );
            }

            $missingTables = $this->missingRequiredTables($schemaName);

            if ($missingTables !== []) {
                return $this->fail(
                    tenant: $tenant,
                    schemaName: $schemaName,
                    schemaExists: true,
                    migrated: false,
                    message: 'Tenant incompleto. Existem tabelas obrigatórias ausentes.',
                    operation: 'tenants_validate',
                    missingTables: $missingTables,
                );
            }

            return TenantProvisioningResult::make(
                tenantCode: (string) $tenant->code,
                schemaName: $schemaName,
                success: true,
                schemaExists: true,
                migrated: false,
            );
        } catch (Throwable $throwable) {
            $this->logExpectedFailure($tenant, $schemaName, 'tenants_validate', $throwable->getMessage(), $throwable::class);

            return TenantProvisioningResult::make(
                tenantCode: (string) $tenant->code,
                schemaName: $schemaName,
                success: false,
                schemaExists: false,
                migrated: false,
                errors: [$throwable->getMessage()],
            );
        }
    }

    public function migrate(Tenant $tenant, bool $force = false): TenantProvisioningResult
    {
        $schemaName = (string) $tenant->schema_name;

        try {
            $this->tenantSchemaService->assertValidSchemaName($schemaName);

            $schemaExists = $this->tenantSchemaService->schemaExists($schemaName);

            if (! $schemaExists) {
                return $this->fail(
                    tenant: $tenant,
                    schemaName: $schemaName,
                    schemaExists: false,
                    migrated: false,
                    message: 'Schema não existe.',
                    operation: 'tenants_migrate',
                );
            }

            $this->tenantMigrationService->runTenantMigrations($schemaName, $force);

            $missingTables = $this->missingRequiredTables($schemaName);

            if ($missingTables !== []) {
                return $this->fail(
                    tenant: $tenant,
                    schemaName: $schemaName,
                    schemaExists: true,
                    migrated: true,
                    message: 'Migrations executadas, mas o tenant continua incompleto.',
                    operation: 'tenants_migrate',
                    missingTables: $missingTables,
                );
            }

            return TenantProvisioningResult::make(
                tenantCode: (string) $tenant->code,
                schemaName: $schemaName,
                success: true,
                schemaExists: true,
                migrated: true,
            );
        } catch (Throwable $throwable) {
            $this->logExpectedFailure($tenant, $schemaName, 'tenants_migrate', $throwable->getMessage(), $throwable::class);

            return TenantProvisioningResult::make(
                tenantCode: (string) $tenant->code,
                schemaName: $schemaName,
                success: false,
                schemaExists: false,
                migrated: false,
                errors: [$throwable->getMessage()],
            );
        }
    }

    /**
     * @return array<int, string>
     */
    private function missingRequiredTables(string $schemaName): array
    {
        $missingTables = [];

        foreach (TenantRequiredTables::all() as $tableName) {
            if (! $this->tenantSchemaService->tableExists($schemaName, $tableName)) {
                $missingTables[] = $tableName;
            }
        }

        return $missingTables;
    }

    /**
     * @param  array<int, string>  $missingTables
     */
    private function fail(
        Tenant $tenant,
        string $schemaName,
        bool $schemaExists,
        bool $migrated,
        string $message,
        string $operation,
        array $missingTables = [],
    ): TenantProvisioningResult {
        $this->logExpectedFailure($tenant, $schemaName, $operation, $message, null, $missingTables);

        return TenantProvisioningResult::make(
            tenantCode: (string) $tenant->code,
            schemaName: $schemaName,
            success: false,
            schemaExists: $schemaExists,
            migrated: $migrated,
            missingTables: $missingTables,
            errors: [$message],
        );
    }

    /**
     * @param  array<int, string>  $missingTables
     */
    private function logExpectedFailure(
        Tenant $tenant,
        string $schemaName,
        string $operation,
        string $message,
        ?string $errorClass = null,
        array $missingTables = [],
    ): void {
        $context = [
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'schema_name' => $schemaName,
            'command' => $operation,
            'error_class' => $errorClass,
            'missing_tables' => $missingTables,
        ];

        try {
            $this->logPersistenceService->logSystemWarning(
                message: $message,
                category: 'tenant-operations',
                operation: $operation,
                context: $context,
                processingStatus: 'failed',
            );
        } catch (Throwable $loggingThrowable) {
            Log::warning('Falha ao persistir log operacional de tenant.', [
                'operation' => $operation,
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
                'schema_name' => $schemaName,
                'error_class' => $loggingThrowable::class,
                'message' => $loggingThrowable->getMessage(),
            ]);
        }
    }

    private function logUnexpectedFailure(
        Throwable $throwable,
        Tenant $tenant,
        string $schemaName,
        string $operation,
    ): void {
        try {
            $this->logPersistenceService->logSystemError(
                throwable: $throwable,
                category: 'tenant-operations',
                operation: $operation,
            );
        } catch (Throwable) {
        }

        Log::error('Erro inesperado em operação de tenant.', [
            'operation' => $operation,
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'schema_name' => $schemaName,
            'error_class' => $throwable::class,
            'message' => $throwable->getMessage(),
        ]);
    }
}
