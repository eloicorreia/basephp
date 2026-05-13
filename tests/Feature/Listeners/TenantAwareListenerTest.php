<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantAwareListenerTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_it_restores_tenant_context_and_search_path_inside_listener(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }
        $tenant = $this->createTenant(code: 'tenant-listener-'.str_replace('-', '', (string) Str::uuid()), status: 'active', schemaName: 'tenant_listener_'.str_replace('-', '', (string) Str::uuid()));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $tenantContext = new TenantContext;
        $searchPathService = new TenantSearchPathService;
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);
        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        TestTenantAwareQueuedListener::reset();

        try {
            (new TestTenantAwareQueuedListener)->handle(new TestTenantAwareEvent($tenant->id));
            $this->assertTrue(TestTenantAwareQueuedListener::$handled);
            $this->assertSame($tenant->id, TestTenantAwareQueuedListener::$resolvedTenantId);
            $this->assertSame($tenant->schema_name, TestTenantAwareQueuedListener::$schemaDuringHandle);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
            TestTenantAwareQueuedListener::reset();
        }
    }

    public function test_it_restores_previous_tenant_context_after_listener_execution(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }
        $outer = $this->createTenant(code: 'listener-outer-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12), schemaName: 'tenant_listener_outer_'.str_replace('-', '', (string) Str::uuid()));
        $inner = $this->createTenant(code: 'listener-inner-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12), schemaName: 'tenant_listener_inner_'.str_replace('-', '', (string) Str::uuid()));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $outer->schema_name));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $inner->schema_name));
        $tenantContext = new TenantContext;
        $searchPathService = new TenantSearchPathService;
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);
        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);
        TestTenantAwareQueuedListener::reset();

        try {
            $executionManager->run($outer, function () use ($inner, $outer, $tenantContext): void {
                (new TestTenantAwareQueuedListener)->handle(new TestTenantAwareEvent($inner->id));
                $this->assertSame($outer->id, $tenantContext->require()->id);
            });
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $outer->schema_name));
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $inner->schema_name));
            TestTenantAwareQueuedListener::reset();
        }
    }

    public function test_it_restores_public_schema_after_listener_execution(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }
        $tenant = $this->createTenant(code: 'listener-public-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12), schemaName: 'tenant_listener_public_'.str_replace('-', '', (string) Str::uuid()));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));
        TestTenantAwareQueuedListener::reset();

        try {
            (new TestTenantAwareQueuedListener)->handle(new TestTenantAwareEvent($tenant->id));
            $row = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($row);
            $this->assertSame('public', $row->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
            TestTenantAwareQueuedListener::reset();
        }
    }
}

final readonly class TestTenantAwareEvent
{
    public function __construct(public int $tenantId) {}
}

final class TestTenantAwareQueuedListener implements ShouldQueue
{
    public static bool $handled = false;

    public static ?int $resolvedTenantId = null;

    public static ?string $schemaDuringHandle = null;

    public function handle(TestTenantAwareEvent $event): void
    {
        $executionManager = app(TenantExecutionManager::class);
        $tenant = Tenant::query()->findOrFail($event->tenantId);
        $executionManager->run($tenant, function () use ($tenant): void {
            self::$handled = true;
            self::$resolvedTenantId = $tenant->id;
            $row = DB::selectOne('select current_schema() as schema');
            self::$schemaDuringHandle = $row !== null && isset($row->schema) ? (string) $row->schema : null;
        });
    }

    public static function reset(): void
    {
        self::$handled = false;
        self::$resolvedTenantId = null;
        self::$schemaDuringHandle = null;
    }
}
