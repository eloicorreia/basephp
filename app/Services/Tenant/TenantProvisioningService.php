<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\User;
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
        $tenant = $this->createProvisioningTenant($code, $name, $schemaName);

        try {
            $this->tenantSchemaService->createSchema($schemaName);
            $this->tenantSchemaService->setSearchPath($schemaName);

            $this->tenantMigrationService->runTenantMigrations($schemaName, $force);
            $this->tenantSeederService->runTenantSeeders($force);
        } catch (Throwable $throwable) {
            $this->safeResetSearchPath();
            $this->markTenantAsFailed($tenant, $throwable);

            throw $throwable;
        } finally {
            $this->safeResetSearchPath();
        }

        return $this->markTenantAsActive($tenant);
    }

    private function createProvisioningTenant(string $code, string $name, string $schemaName): Tenant
    {
        return DB::transaction(fn (): Tenant => Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $name,
            'schema_name' => $schemaName,
            'status' => 'provisioning',
        ]));
    }

    private function markTenantAsActive(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => 'active',
        ]);

        $tenant->refresh();

        try {
            $this->logPersistenceService->logAudit(
                action: 'tenant.provisioned',
                auditableType: Tenant::class,
                auditableId: $tenant->id,
                beforeData: [
                    'status' => 'provisioning',
                ],
                afterData: [
                    'code' => $tenant->code,
                    'schema_name' => $tenant->schema_name,
                    'status' => $tenant->status,
                ],
                userId: auth()->id(),
                userRole: $this->currentUserRoleCode(),
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
        } catch (Throwable) {
        }

        return $tenant;
    }

    private function markTenantAsFailed(Tenant $tenant, Throwable $throwable): void
    {
        try {
            $tenant->update([
                'status' => 'error',
            ]);
        } catch (Throwable) {
        }

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
    }

    private function safeResetSearchPath(): void
    {
        try {
            $this->tenantSchemaService->resetSearchPath();
        } catch (Throwable) {
        }
    }

    private function currentUserRoleCode(): ?string
    {
        $user = auth()->user();

        return $user instanceof User ? $user->role?->code : null;
    }
}
