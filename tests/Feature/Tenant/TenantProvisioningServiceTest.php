<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantMigrationService;
use App\Services\Tenant\TenantProvisioningService;
use App\Services\Tenant\TenantSchemaService;
use App\Services\Tenant\TenantSeederService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class TenantProvisioningServiceTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $schemasToDrop = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->schemasToDrop) as $schemaName) {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $schemaName));
        }

        parent::tearDown();
    }

    public function test_it_persists_error_status_when_structural_provisioning_fails(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-failure-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $migrationService = new class (app(TenantSchemaService::class)) extends TenantMigrationService {
            public function runTenantMigrations(string $schemaName, bool $force = false): void
            {
                throw new RuntimeException('Falha controlada nas migrations do tenant.');
            }
        };

        $service = new TenantProvisioningService(
            tenantSchemaService: app(TenantSchemaService::class),
            tenantMigrationService: $migrationService,
            tenantSeederService: app(TenantSeederService::class),
            logPersistenceService: app(LogPersistenceService::class),
        );

        try {
            $service->provision(
                code: $code,
                name: 'Tenant Failure',
                schemaName: $schemaName,
            );

            $this->fail('A falha controlada do provisionamento deveria ter sido relançada.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha controlada nas migrations do tenant.', $exception->getMessage());
        }

        $tenant = Tenant::query()->where('code', $code)->firstOrFail();

        $this->assertSame('error', $tenant->status);
        $this->assertSame('public', $this->currentSchema());
        $this->assertTrue($this->schemaExists($schemaName));
        $this->assertDatabaseHas('system_logs', [
            'category' => 'tenant',
            'operation' => 'provision',
            'processing_status' => 'error',
            'message' => 'Falha controlada nas migrations do tenant.',
        ]);
    }

    public function test_it_runs_tenant_migrations_with_repository_inside_tenant_schema(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-success-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $tenant = app(TenantProvisioningService::class)->provision(
            code: $code,
            name: 'Tenant Success',
            schemaName: $schemaName,
        );

        $this->assertSame('active', $tenant->status);
        $this->assertSame('public', $this->currentSchema());
        $this->assertTrue($this->schemaTableExists($schemaName, 'migrations'));
        $this->assertTrue($this->schemaTableExists($schemaName, 'mail_configs'));
        $this->assertTrue($this->schemaTableExists($schemaName, 'business_logs'));
        $this->assertGreaterThan(0, $this->tenantMigrationCount($schemaName));
    }

    private function newSchemaName(): string
    {
        return 'tenant_prov_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 16);
    }

    private function currentSchema(): string
    {
        return (string) DB::selectOne('SELECT current_schema() AS schema')->schema;
    }

    private function schemaExists(string $schemaName): bool
    {
        $row = DB::selectOne(
            'SELECT EXISTS (
                SELECT 1
                FROM information_schema.schemata
                WHERE schema_name = ?
            ) AS schema_exists',
            [$schemaName]
        );

        return filter_var($row->schema_exists, FILTER_VALIDATE_BOOLEAN);
    }

    private function schemaTableExists(string $schemaName, string $tableName): bool
    {
        $qualifiedTableName = sprintf('"%s"."%s"', $schemaName, $tableName);
        $row = DB::selectOne('SELECT to_regclass(?) AS relation', [$qualifiedTableName]);

        return $row->relation !== null;
    }

    private function tenantMigrationCount(string $schemaName): int
    {
        $row = DB::selectOne(sprintf('SELECT COUNT(*) AS aggregate FROM "%s"."migrations"', $schemaName));

        return (int) $row->aggregate;
    }
}
