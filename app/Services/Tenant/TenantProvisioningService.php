<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\DTOs\Tenant\TenantProvisioningResult;
use App\Exceptions\TenantConflictException;
use App\Models\Tenant;
use App\Models\TenantProvisioningRun;
use App\Services\Logging\LogPersistenceService;
use App\Support\Tenant\TenantRequiredTables;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final readonly class TenantProvisioningService
{
    public function __construct(
        private TenantSchemaService $tenantSchemaService,
        private TenantMigrationService $tenantMigrationService,
        private ?TenantSeederService $tenantSeederService,
        private LogPersistenceService $logPersistenceService,
    ) {}

    public function createAndProvision(string $code, string $name, string $schemaName, bool $force = false): Tenant
    {
        $this->tenantSchemaService->assertValidSchemaName($schemaName);

        return $this->withProvisioningLock($code, $schemaName, function () use ($code, $name, $schemaName, $force): Tenant {
            $run = $this->startProvisioningRun($code, $schemaName);
            $tenant = null;

            try {
                $tenant = $this->resolveTenantForProvisioning($code, $name, $schemaName);
                $this->attachTenantToRun($run, $tenant);

                $this->tenantSchemaService->createSchema($schemaName);
                $this->tenantMigrationService->runTenantMigrations($schemaName, $force);

                if ($this->tenantSeederService instanceof TenantSeederService) {
                    $searchPathService = app(TenantSearchPathService::class);

                    try {
                        $searchPathService->setTenantSchema($schemaName);
                        $this->tenantSeederService->runTenantSeeders($force);
                    } finally {
                        $searchPathService->resetToPublic();
                    }
                }

                $missingTables = $this->missingRequiredTables($schemaName);

                if ($missingTables !== []) {
                    throw new RuntimeException('Tenant provisionado com schema incompleto: '.implode(', ', $missingTables));
                }

                $tenant->forceFill([
                    'status' => Tenant::STATUS_ACTIVE,
                ])->save();

                $this->finishProvisioningRun($run, 'success');

                return $tenant->refresh();
            } catch (Throwable $throwable) {
                if ($tenant instanceof Tenant) {
                    $tenant->forceFill([
                        'status' => Tenant::STATUS_ERROR,
                    ])->save();

                    $this->attachTenantToRun($run, $tenant);
                    $this->logCreateAndProvisionFailure($throwable, $tenant);
                }

                $this->finishProvisioningRun($run, 'failed', $throwable);

                throw $throwable;
            }
        });
    }

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

            $validationResult = $this->validate($tenant);

            if ($validationResult->failed()) {
                return TenantProvisioningResult::make(
                    tenantCode: $validationResult->tenantCode,
                    schemaName: $validationResult->schemaName,
                    success: false,
                    schemaExists: $validationResult->schemaExists,
                    migrated: true,
                    missingTables: $validationResult->missingTables,
                    errors: $validationResult->errors,
                );
            }

            return TenantProvisioningResult::make(
                tenantCode: (string) $tenant->code,
                schemaName: $schemaName,
                success: true,
                schemaExists: $schemaExists,
                migrated: true,
            );
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
        $context = [
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'schema_name' => $schemaName,
            'command' => $operation,
            'error_class' => $throwable::class,
        ];

        try {
            $this->logPersistenceService->logSystemWarning(
                message: $throwable->getMessage() !== '' ? $throwable->getMessage() : 'Erro inesperado em operação de tenant.',
                category: 'tenant-operations',
                operation: $operation,
                context: $context,
                processingStatus: 'error',
            );

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

    private function resolveTenantForProvisioning(string $code, string $name, string $schemaName): Tenant
    {
        $existingTenant = Tenant::query()
            ->where('code', $code)
            ->orWhere('schema_name', $schemaName)
            ->first();

        if ($existingTenant instanceof Tenant) {
            if ($existingTenant->code !== $code || $existingTenant->schema_name !== $schemaName) {
                throw TenantConflictException::codeOrSchemaAlreadyExists();
            }

            if ($existingTenant->status === Tenant::STATUS_ERROR) {
                $existingTenant->forceFill([
                    'name' => $name,
                    'status' => Tenant::STATUS_PROVISIONING,
                ])->save();
            }

            return $existingTenant;
        }

        try {
            return Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => $code,
                'name' => $name,
                'schema_name' => $schemaName,
                'status' => Tenant::STATUS_PROVISIONING,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw TenantConflictException::codeOrSchemaAlreadyExists();
            }

            throw $exception;
        }
    }

    private function logCreateAndProvisionFailure(Throwable $throwable, Tenant $tenant): void
    {
        try {
            $this->logPersistenceService->logSystemWarning(
                message: $throwable->getMessage(),
                category: 'tenant-operations',
                operation: 'tenants_create_and_provision',
                context: [
                    'tenant_id' => $tenant->id,
                    'tenant_code' => $tenant->code,
                    'schema_name' => $tenant->schema_name,
                    'error_class' => $throwable::class,
                ],
                processingStatus: 'error',
            );
        } catch (Throwable $loggingThrowable) {
            Log::warning('Falha ao persistir log operacional de criação/provisionamento de tenant.', [
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->code,
                'schema_name' => $tenant->schema_name,
                'error_class' => $loggingThrowable::class,
                'message' => $loggingThrowable->getMessage(),
            ]);
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23505';
    }

    /**
     * Serializes provisioning attempts for the same tenant identity before the
     * unique indexes are reached, avoiding duplicated schema work under load.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function withProvisioningLock(string $code, string $schemaName, callable $callback): mixed
    {
        $keys = $this->provisioningLockKeys($code, $schemaName);
        $acquired = false;

        try {
            DB::select('SELECT pg_advisory_lock(?, ?)', $keys);
            $acquired = true;

            return $callback();
        } finally {
            if ($acquired) {
                DB::select('SELECT pg_advisory_unlock(?, ?)', $keys);
            }
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function provisioningLockKeys(string $code, string $schemaName): array
    {
        return [
            $this->signedCrc32('tenant-provisioning'),
            $this->signedCrc32($code.'|'.$schemaName),
        ];
    }

    private function signedCrc32(string $value): int
    {
        $hash = crc32($value);

        return $hash > 2147483647 ? $hash - 4294967296 : $hash;
    }

    private function startProvisioningRun(string $code, string $schemaName): TenantProvisioningRun
    {
        return TenantProvisioningRun::query()->create([
            'tenant_code' => $code,
            'schema_name' => $schemaName,
            'operation' => 'tenants_create_and_provision',
            'status' => 'running',
            'started_at' => now(),
            'request_id' => request()->attributes->get('request_id'),
            'trace_id' => request()->attributes->get('trace_id'),
        ]);
    }

    private function attachTenantToRun(TenantProvisioningRun $run, Tenant $tenant): void
    {
        if ($run->tenant_id !== null) {
            return;
        }

        $run->forceFill([
            'tenant_id' => $tenant->id,
        ])->save();
    }

    private function finishProvisioningRun(TenantProvisioningRun $run, string $status, ?Throwable $throwable = null): void
    {
        $run->forceFill([
            'status' => $status,
            'finished_at' => now(),
            'error_message' => $throwable instanceof Throwable ? Str::limit($throwable->getMessage(), 4000, '') : null,
            'error_class' => $throwable instanceof Throwable ? $throwable::class : null,
        ])->save();
    }
}
