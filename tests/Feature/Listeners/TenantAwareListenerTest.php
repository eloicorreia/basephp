<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantAwareListenerTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);

        Event::listen(
            TestTenantAwareEvent::class,
            TestTenantAwareQueuedListener::class
        );
    }

    public function test_it_restores_tenant_context_and_search_path_inside_listener(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->createTenant(
            code: 'tenant-listener',
            status: 'active',
            schemaName: 'tenant_listener_test'
        );

        DB::statement('CREATE SCHEMA IF NOT EXISTS "' . $tenant->schema_name . '"');

        TestTenantAwareQueuedListener::resetState();

        event(new TestTenantAwareEvent($tenant->id));

        $this->assertTrue(TestTenantAwareQueuedListener::$handled);
        $this->assertSame($tenant->id, TestTenantAwareQueuedListener::$resolvedTenantId);
        $this->assertSame($tenant->schema_name, TestTenantAwareQueuedListener::$schemaDuringHandle);

        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);

        $this->assertNull($tenantContext->get());

        $currentSchema = DB::selectOne('select current_schema() as schema');
        $this->assertSame('public', $currentSchema->schema);

        DB::statement('DROP SCHEMA IF EXISTS "' . $tenant->schema_name . '" CASCADE');
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

    public static function resetState(): void
    {
        self::$handled = false;
        self::$resolvedTenantId = null;
        self::$schemaDuringHandle = null;
    }

    public function handle(
        TestTenantAwareEvent $event,
        TenantExecutionManager $executionManager
    ): void {
        $tenant = Tenant::query()->findOrFail($event->tenantId);

        $executionManager->run($tenant, function () use ($tenant): void {
            self::$handled = true;
            self::$resolvedTenantId = $tenant->id;

            $currentSchema = DB::selectOne('select current_schema() as schema');
            self::$schemaDuringHandle = $currentSchema->schema;
        });
    }
}