<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Exceptions\TenantConflictException;
use App\Models\Tenant;
use App\Models\TenantProvisioningRun;
use App\Services\Logging\LogPersistenceService;
use App\Services\Tenant\TenantMigrationService;
use App\Services\Tenant\TenantProvisioningService;
use App\Services\Tenant\TenantSchemaService;
use App\Services\Tenant\TenantSeederService;
use App\Support\Logging\SensitiveDataSanitizer;
use Illuminate\Database\QueryException;
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
        $code = 'tenant-failure-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $migrationService = new class(app(TenantSchemaService::class)) extends TenantMigrationService
        {
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
            sensitiveDataSanitizer: app(SensitiveDataSanitizer::class),
        );

        try {
            $service->createAndProvision(
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
            'category' => 'tenant-operations',
            'operation' => TenantProvisioningRun::OPERATION_TENANTS_CREATE_AND_PROVISION,
            'processing_status' => 'error',
            'message' => 'Falha controlada nas migrations do tenant.',
        ]);
    }

    public function test_it_sanitizes_sensitive_error_message_before_persisting_provisioning_run(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-secret-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $migrationService = new class(app(TenantSchemaService::class)) extends TenantMigrationService
        {
            public function runTenantMigrations(string $schemaName, bool $force = false): void
            {
                throw new RuntimeException(
                    'Falha password=PlainPassword123 token=tenant-token-456 Authorization: Bearer bearer-secret cookie=session-secret secret=client-secret session=abc csrf_token=csrf-secret credentials=credential-secret'
                );
            }
        };

        $service = new TenantProvisioningService(
            tenantSchemaService: app(TenantSchemaService::class),
            tenantMigrationService: $migrationService,
            tenantSeederService: app(TenantSeederService::class),
            logPersistenceService: app(LogPersistenceService::class),
            sensitiveDataSanitizer: app(SensitiveDataSanitizer::class),
        );

        try {
            $service->createAndProvision(
                code: $code,
                name: 'Tenant Secret Failure',
                schemaName: $schemaName,
            );

            $this->fail('A falha com dados sensíveis deveria ter sido relançada.');
        } catch (RuntimeException) {
        }

        $run = TenantProvisioningRun::query()
            ->where('tenant_code', $code)
            ->where('schema_name', $schemaName)
            ->firstOrFail();

        $this->assertSame(TenantProvisioningRun::STATUS_FAILED, $run->status);
        $this->assertSame(RuntimeException::class, $run->error_class);
        $this->assertIsString($run->error_message);
        $this->assertStringContainsString('password=***', $run->error_message);
        $this->assertStringContainsString('token=***', $run->error_message);
        $this->assertStringContainsString('Authorization: ***', $run->error_message);
        $this->assertStringContainsString('cookie=***', $run->error_message);
        $this->assertStringContainsString('secret=***', $run->error_message);
        $this->assertStringContainsString('session=***', $run->error_message);
        $this->assertStringContainsString('csrf_token=***', $run->error_message);
        $this->assertStringContainsString('credentials=***', $run->error_message);
        $this->assertStringNotContainsString('PlainPassword123', $run->error_message);
        $this->assertStringNotContainsString('tenant-token-456', $run->error_message);
        $this->assertStringNotContainsString('bearer-secret', $run->error_message);
        $this->assertStringNotContainsString('session-secret', $run->error_message);
        $this->assertStringNotContainsString('client-secret', $run->error_message);
        $this->assertStringNotContainsString('csrf-secret', $run->error_message);
        $this->assertStringNotContainsString('credential-secret', $run->error_message);
    }

    public function test_it_runs_tenant_migrations_with_repository_inside_tenant_schema(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-success-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $tenant = app(TenantProvisioningService::class)->createAndProvision(
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
        $this->assertDatabaseHas('tenant_provisioning_runs', [
            'tenant_id' => $tenant->id,
            'tenant_code' => $code,
            'schema_name' => $schemaName,
            'operation' => TenantProvisioningRun::OPERATION_TENANTS_CREATE_AND_PROVISION,
            'status' => TenantProvisioningRun::STATUS_SUCCESS,
            'error_message' => null,
            'error_class' => null,
        ]);
    }

    public function test_it_returns_existing_active_tenant_when_provisioning_is_repeated(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-idem-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $firstTenant = app(TenantProvisioningService::class)->createAndProvision(
            code: $code,
            name: 'Tenant Idempotente',
            schemaName: $schemaName,
        );

        $secondTenant = app(TenantProvisioningService::class)->createAndProvision(
            code: $code,
            name: 'Tenant Idempotente Renomeado',
            schemaName: $schemaName,
        );

        $this->assertSame($firstTenant->id, $secondTenant->id);
        $this->assertSame('Tenant Idempotente', $secondTenant->name);
        $this->assertSame('active', $secondTenant->status);
        $this->assertSame(1, Tenant::query()->where('code', $code)->count());
    }

    public function test_it_rebuilds_active_tenant_when_schema_is_incomplete(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-rebuild-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 10);
        $this->schemasToDrop[] = $schemaName;

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schemaName));

        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Tenant Incompleto',
            'schema_name' => $schemaName,
            'status' => 'active',
        ]);

        $rebuiltTenant = app(TenantProvisioningService::class)->createAndProvision(
            code: $code,
            name: 'Tenant Incompleto',
            schemaName: $schemaName,
        );

        $this->assertSame($tenant->id, $rebuiltTenant->id);
        $this->assertSame('active', $rebuiltTenant->status);
        $this->assertTrue($this->schemaTableExists($schemaName, 'migrations'));
        $this->assertTrue($this->schemaTableExists($schemaName, 'mail_configs'));
        $this->assertTrue($this->schemaTableExists($schemaName, 'business_logs'));
        $this->assertTrue($this->schemaTableExists($schemaName, 'integration_logs'));
        $this->assertTrue($this->schemaTableExists($schemaName, 'email_dispatch_logs'));
    }

    public function test_it_retries_failed_tenant_provisioning_with_same_code_and_schema(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-retry-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $migrationService = new class(app(TenantSchemaService::class)) extends TenantMigrationService
        {
            public function runTenantMigrations(string $schemaName, bool $force = false): void
            {
                throw new RuntimeException('Falha temporária nas migrations do tenant.');
            }
        };

        $failingService = new TenantProvisioningService(
            tenantSchemaService: app(TenantSchemaService::class),
            tenantMigrationService: $migrationService,
            tenantSeederService: app(TenantSeederService::class),
            logPersistenceService: app(LogPersistenceService::class),
            sensitiveDataSanitizer: app(SensitiveDataSanitizer::class),
        );

        try {
            $failingService->createAndProvision(
                code: $code,
                name: 'Tenant Retry',
                schemaName: $schemaName,
            );

            $this->fail('A falha temporária deveria ter sido relançada.');
        } catch (RuntimeException) {
        }

        $failedTenant = Tenant::query()->where('code', $code)->firstOrFail();

        $this->assertSame('error', $failedTenant->status);
        $this->assertDatabaseHas('tenant_provisioning_runs', [
            'tenant_id' => $failedTenant->id,
            'tenant_code' => $code,
            'schema_name' => $schemaName,
            'operation' => TenantProvisioningRun::OPERATION_TENANTS_CREATE_AND_PROVISION,
            'status' => TenantProvisioningRun::STATUS_FAILED,
            'error_class' => RuntimeException::class,
        ]);

        $retriedTenant = app(TenantProvisioningService::class)->createAndProvision(
            code: $code,
            name: 'Tenant Retry Recuperado',
            schemaName: $schemaName,
        );

        $this->assertSame($failedTenant->id, $retriedTenant->id);
        $this->assertSame('Tenant Retry Recuperado', $retriedTenant->name);
        $this->assertSame('active', $retriedTenant->status);
        $this->assertTrue($this->schemaTableExists($schemaName, 'migrations'));
    }

    public function test_it_rejects_conflicting_tenant_code_or_schema(): void
    {
        $schemaName = $this->newSchemaName();
        $conflictingSchemaName = $this->newSchemaName();
        $code = 'tenant-conflict-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 8);

        Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Tenant Existente',
            'schema_name' => $schemaName,
            'status' => 'inactive',
        ]);

        $this->expectException(TenantConflictException::class);
        $this->expectExceptionMessage('Já existe tenant usando o código ou schema informado.');

        app(TenantProvisioningService::class)->createAndProvision(
            code: $code,
            name: 'Tenant Conflitante',
            schemaName: $conflictingSchemaName,
        );
    }

    public function test_tenant_code_and_schema_name_have_unique_database_constraints(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-unique-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 10);

        Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Tenant Único',
            'schema_name' => $schemaName,
            'status' => Tenant::STATUS_INACTIVE,
        ]);

        try {
            Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => $code,
                'name' => 'Tenant Duplicado',
                'schema_name' => $this->newSchemaName(),
                'status' => Tenant::STATUS_INACTIVE,
            ]);

            $this->fail('A constraint única de tenants.code deveria rejeitar duplicidade.');
        } catch (QueryException $exception) {
            $this->assertSame('23505', $exception->getCode());
        }
    }

    public function test_tenant_schema_name_has_unique_database_constraint(): void
    {
        $schemaName = $this->newSchemaName();

        Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-schema-a-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 8),
            'name' => 'Tenant Schema A',
            'schema_name' => $schemaName,
            'status' => Tenant::STATUS_INACTIVE,
        ]);

        try {
            Tenant::query()->create([
                'uuid' => (string) Str::uuid(),
                'code' => 'tenant-schema-b-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 8),
                'name' => 'Tenant Schema B',
                'schema_name' => $schemaName,
                'status' => Tenant::STATUS_INACTIVE,
            ]);

            $this->fail('A constraint única de tenants.schema_name deveria rejeitar duplicidade.');
        } catch (QueryException $exception) {
            $this->assertSame('23505', $exception->getCode());
        }
    }

    public function test_provisioning_lock_does_not_block_normal_create_and_provision_flow(): void
    {
        $schemaName = $this->newSchemaName();
        $code = 'tenant-lock-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
        $this->schemasToDrop[] = $schemaName;

        $tenant = app(TenantProvisioningService::class)->createAndProvision(
            code: $code,
            name: 'Tenant Lock',
            schemaName: $schemaName,
        );

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->status);
        $this->assertSame('public', $this->currentSchema());
        $this->assertSame(1, TenantProvisioningRun::query()
            ->where('tenant_code', $code)
            ->where('schema_name', $schemaName)
            ->where('status', TenantProvisioningRun::STATUS_SUCCESS)
            ->count());
    }

    private function newSchemaName(): string
    {
        return 'tenant_prov_'.substr(str_replace('-', '', (string) Str::uuid()), 0, 16);
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
