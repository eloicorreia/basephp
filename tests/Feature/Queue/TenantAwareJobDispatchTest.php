<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Models\Tenant;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantAwareJobDispatchTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    use RefreshDatabase;

    public function test_it_executes_should_queue_job_with_restored_tenant_context(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        config(['queue.default' => 'sync']);

        $tenant = $this->createTenant(
            code: 'tenant-job',
            status: 'active',
            schemaName: 'tenant_job_test'
        );

        DB::statement('CREATE SCHEMA IF NOT EXISTS "' . $tenant->schema_name . '"');

        TestTenantAwareJob::$handled = false;
        TestTenantAwareJob::$tenantIdSeen = null;
        TestTenantAwareJob::$schemaSeen = null;

        dispatch(new TestTenantAwareJob($tenant->id));

        $this->assertTrue(TestTenantAwareJob::$handled);
        $this->assertSame($tenant->id, TestTenantAwareJob::$tenantIdSeen);
        $this->assertSame($tenant->schema_name, TestTenantAwareJob::$schemaSeen);

        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);

        $this->assertNull($tenantContext->get());

        $currentSchema = DB::selectOne('select current_schema() as schema');
        $this->assertSame('public', $currentSchema->schema);

        DB::statement('DROP SCHEMA IF EXISTS "' . $tenant->schema_name . '" CASCADE');
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
            self::$handled = true;
            self::$tenantIdSeen = app(TenantContext::class)->require()->id;

            $currentSchema = DB::selectOne('select current_schema() as schema');
            self::$schemaSeen = $currentSchema->schema;
        });
    }
}