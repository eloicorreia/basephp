<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantAwareJobDispatchTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        TestTenantAwareJob::reset();
    }

    protected function tearDown(): void
    {
        TestTenantAwareJob::reset();

        parent::tearDown();
    }

    public function test_it_executes_should_queue_job_with_restored_tenant_context(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenantCode = 'tenant-job-' . str_replace('-', '', (string) Str::uuid());
        $tenantSchema = 'tenant_job_' . str_replace('-', '', (string) Str::uuid());

        $tenant = $this->createTenant(
            code: $tenantCode,
            status: 'active',
            schemaName: $tenantSchema
        );

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $tenantContext = new TenantContext();
        $searchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        try {
            $job = new TestTenantAwareJob($tenant->id);
            $job->handle();

            $this->assertTrue(TestTenantAwareJob::$handled);
            $this->assertSame($tenant->id, TestTenantAwareJob::$tenantIdSeen);
            $this->assertSame($tenant->schema_name, TestTenantAwareJob::$schemaSeen);
            $this->assertNull($tenantContext->get());

            $currentSchema = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($currentSchema);
            $this->assertSame('public', $currentSchema->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_restores_previous_tenant_context_after_job_execution(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $outerTenant = $this->createTenant(
            code: 'tenant-outer-' . str_replace('-', '', (string) Str::uuid()),
            status: 'active',
            schemaName: 'tenant_outer_' . str_replace('-', '', (string) Str::uuid())
        );

        $jobTenant = $this->createTenant(
            code: 'tenant-inner-' . str_replace('-', '', (string) Str::uuid()),
            status: 'active',
            schemaName: 'tenant_inner_' . str_replace('-', '', (string) Str::uuid())
        );

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $outerTenant->schema_name));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $jobTenant->schema_name));

        $tenantContext = new TenantContext();
        $searchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        try {
            $executionManager->run($outerTenant, function () use ($tenantContext, $outerTenant, $jobTenant): void {
                $this->assertSame($outerTenant->id, $tenantContext->require()->id);

                $job = new TestTenantAwareJob($jobTenant->id);
                $job->handle();

                $this->assertTrue(TestTenantAwareJob::$handled);
                $this->assertSame($jobTenant->id, TestTenantAwareJob::$tenantIdSeen);
                $this->assertSame($jobTenant->schema_name, TestTenantAwareJob::$schemaSeen);

                $this->assertSame($outerTenant->id, $tenantContext->require()->id);

                $currentSchema = DB::selectOne('select current_schema() as schema');
                $this->assertNotNull($currentSchema);
                $this->assertSame($outerTenant->schema_name, $currentSchema->schema);
            });

            $this->assertNull($tenantContext->get());

            $currentSchema = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($currentSchema);
            $this->assertSame('public', $currentSchema->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $outerTenant->schema_name));
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $jobTenant->schema_name));
        }
    }
}

final class TestTenantAwareJob implements ShouldQueue
{
    use InteractsWithTenantContext;

    public static bool $handled = false;
    public static ?int $tenantIdSeen = null;
    public static ?string $schemaSeen = null;

    public function __construct(
        protected int|string $tenantId
    ) {
    }

    public function handle(): void
    {
        $this->runInTenantContext(function (): void {
            /** @var TenantContext $tenantContext */
            $tenantContext = app(TenantContext::class);

            self::$handled = true;
            self::$tenantIdSeen = $tenantContext->require()->id;

            $currentSchema = DB::selectOne('select current_schema() as schema');

            self::$schemaSeen = $currentSchema !== null && isset($currentSchema->schema)
                ? (string) $currentSchema->schema
                : null;
        });
    }

    public static function reset(): void
    {
        self::$handled = false;
        self::$tenantIdSeen = null;
        self::$schemaSeen = null;
    }
}