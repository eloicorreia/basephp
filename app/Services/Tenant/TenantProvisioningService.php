<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TenantProvisioningService
{
    public function __construct(
        private readonly TenantSchemaService $tenantSchemaService,
        private readonly TenantMigrationService $tenantMigrationService,
        private readonly TenantSeederService $tenantSeederService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {
    }

    public function provision(
        string $code,
        string $name,
        string $schemaName,
        bool $force = false,
    ): Tenant {
        return DB::transaction(function () use ($code, $name, $schemaName, $force): Tenant {
            $tenant = Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => $code,
                'name' => $name,
                'schema_name' => $schemaName,
                'status' => 'provisioning',
            ]);

            try {
                $this->tenantSchemaService->createSchema($schemaName);
                $this->tenantSchemaService->setSearchPath($schemaName);

                $this->tenantMigrationService->runTenantMigrations($force);
                $this->tenantSeederService->runTenantSeeders($force);

                $tenant->update([
                    'status' => 'active',
                ]);

                $this->logPersistenceService->logAudit(
                    action: 'tenant.provisioned',
                    auditableType: Tenant::class,
                    auditableId: $tenant->id,
                    beforeData: null,
                    afterData: [
                        'code' => $tenant->code,
                        'schema_name' => $tenant->schema_name,
                        'status' => $tenant->status,
                    ],
                    userId: auth()->id(),
                    userRole: auth()->user()?->role?->code,
                );

                $this->logPersistenceService->logSystemInfo(
                    message: 'Tenant provisionado com sucesso.',
                    category: 'tenant',
                    operation: 'provision',
                    userId: auth()->id(),
                    context: [
                        'tenant_id' => $tenant->id,
                        'tenant_code' => $tenant->code,
                        'schema_name' => $tenant->schema_name,
                    ],
                    processingStatus: 'success',
                );

                return $tenant;
            } catch (Throwable $throwable) {
                $tenant->update([
                    'status' => 'error',
                ]);

                try {
                    $this->logPersistenceService->logSystemError(
                        throwable: $throwable,
                        category: 'tenant',
                        operation: 'provision',
                        userId: auth()->id(),
                        httpStatus: 500,
                    );
                } catch (Throwable) {
                }

                throw $throwable;
            } finally {
                $this->tenantSchemaService->resetSearchPath();
            }
        });
    }
}