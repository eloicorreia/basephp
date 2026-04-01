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

        $tenantCode = 'tenant-listener-' . str_replace('-', '', (string) Str::uuid());
        $tenantSchema = 'tenant_listener_' . str_replace('-', '', (string) Str::uuid());

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

        TestTenantAwareQueuedListener::reset();

        try {
            $listener = new TestTenantAwareQueuedListener();
            $listener->handle(new TestTenantAwareEvent($tenant->id));

            $this->assertTrue(TestTenantAwareQueuedListener::$handled);
            $this->assertSame($tenant->id, TestTenantAwareQueuedListener::$resolvedTenantId);
            $this->assertSame($tenant->schema_name, TestTenantAwareQueuedListener::$schemaDuringHandle);
            $this->assertNull($tenantContext->get());

            $currentSchema = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($currentSchema);
            $this->assertSame('public', $currentSchema->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
            TestTenantAwareQueuedListener::reset();
        }
    }
}

final readonly class TestTenantAwareEvent
{
    public function __construct(
        public int $tenantId
    ) {
    }
}

final class TestTenantAwareQueuedListener implements ShouldQueue
{
    public static bool $handled = false;
    public static ?int $resolvedTenantId = null;
    public static ?string $schemaDuringHandle = null;

    public function handle(TestTenantAwareEvent $event): void
    {
        /** @var TenantExecutionManager $executionManager */
        $executionManager = app(TenantExecutionManager::class);

        $tenant = Tenant::query()->findOrFail($event->tenantId);

        $executionManager->run($tenant, function (): void {
            /** @var TenantContext $tenantContext */
            $tenantContext = app(TenantContext::class);

            self::$handled = true;
            self::$resolvedTenantId = $tenantContext->require()->id;

            $currentSchema = DB::selectOne('select current_schema() as schema');

            self::$schemaDuringHandle = $currentSchema !== null && isset($currentSchema->schema)
                ? (string) $currentSchema->schema
                : null;
        });
    }

    public static function reset(): void
    {
        self::$handled = false;
        self::$resolvedTenantId = null;
        self::$schemaDuringHandle = null;
    }
}