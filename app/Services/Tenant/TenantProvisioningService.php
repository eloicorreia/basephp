<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class TenantProvisioningService
{
    public function __construct(
        private readonly TenantSchemaService $tenantSchemaService,
        private readonly TenantMigrationService $tenantMigrationService,
        private readonly TenantSeederService $tenantSeederService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {}

    public function provision(
        string $code,
        string $name,
        string $schemaName,
        bool $force = false,
    ): Tenant {
        $this->tenantSchemaService->assertValidSchemaName($schemaName);

        $tenant = $this->reserveTenantForProvisioning($code, $name, $schemaName);

        if (
            $tenant->status === Tenant::STATUS_ACTIVE
            && $this->tenantSchemaService->schemaExists($tenant->schema_name)
        ) {
            return $tenant;
        }

        if ($tenant->status === Tenant::STATUS_ACTIVE) {
            $tenant->update(['status' => Tenant::STATUS_PROVISIONING]);
            $tenant->refresh();
        }

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

    private function reserveTenantForProvisioning(string $code, string $name, string $schemaName): Tenant
    {
        return DB::transaction(function () use ($code, $name, $schemaName): Tenant {
            $tenant = Tenant::query()
                ->where('code', $code)
                ->orWhere('schema_name', $schemaName)
                ->lockForUpdate()
                ->first();

            if ($tenant === null) {
                return Tenant::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'code' => $code,
                    'name' => $name,
                    'schema_name' => $schemaName,
                    'status' => Tenant::STATUS_PROVISIONING,
                ]);
            }

            if ($tenant->code !== $code || $tenant->schema_name !== $schemaName) {
                throw new RuntimeException('Já existe tenant usando o código ou schema informado.');
            }

            if ($tenant->status === Tenant::STATUS_ACTIVE) {
                return $tenant;
            }

            if (! in_array($tenant->status, [Tenant::STATUS_ERROR, Tenant::STATUS_PROVISIONING], true)) {
                throw new RuntimeException('Tenant existente não pode ser provisionado novamente neste status.');
            }

            $tenant->update([
                'name' => $name,
                'status' => Tenant::STATUS_PROVISIONING,
            ]);

            $tenant->refresh();

            return $tenant;
        });
    }

    private function markTenantAsActive(Tenant $tenant): Tenant
    {
        $tenant->update([
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $tenant->refresh();

        try {
            $this->logPersistenceService->logAudit(
                action: 'tenant.provisioned',
                auditableType: Tenant::class,
                auditableId: $tenant->id,
                beforeData: [
                    'status' => Tenant::STATUS_PROVISIONING,
                ],
                afterData: [
                    'code' => $tenant->code,
                    'schema_name' => $tenant->schema_name,
                    'status' => $tenant->status,
                ],
                userId: AuthenticatedUserId::resolve(),
                userRole: $this->currentUserRoleCode(),
            );

            $this->logPersistenceService->logSystemInfo(
                message: 'Tenant provisionado com sucesso.',
                category: 'tenant',
                operation: 'provision',
                userId: AuthenticatedUserId::resolve(),
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
                'status' => Tenant::STATUS_ERROR,
            ]);
        } catch (Throwable) {
        }

        try {
            $this->logPersistenceService->logSystemError(
                throwable: $throwable,
                category: 'tenant',
                operation: 'provision',
                userId: AuthenticatedUserId::resolve(),
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
