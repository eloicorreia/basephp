<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

final class TenantReprocessCommandFailureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Tenant::query()
            ->where('schema_name', 'like', 'invalid schema%')
            ->delete();
    }

    public function test_it_fails_when_tenant_id_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model');

        $this->artisan('tenant:reprocess 999999');
    }

    public function test_it_ignores_inactive_tenants_when_all_option_is_used(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $inactiveTenant = $this->tenant(status: 'inactive', schemaName: $this->invalidSchemaName());

        try {
            $this->artisan('tenant:reprocess --all')
                ->assertSuccessful();

            $this->assertDatabaseHas('tenants', [
                'id' => $inactiveTenant->id,
                'status' => 'inactive',
            ]);
        } finally {
            $inactiveTenant->delete();
        }
    }

    public function test_it_restores_public_schema_when_processing_callback_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->tenant(status: 'active', schemaName: $this->invalidSchemaName());
        $tenantContext = new TenantContext;
        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(
            TenantExecutionManager::class,
            new TenantExecutionManager($tenantContext, new TenantSearchPathService)
        );

        try {
            try {
                $this->artisan('tenant:reprocess '.$tenant->id)->run();
                $this->fail('Era esperada uma exceção de schema inválido.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Nome de schema inválido.', $exception->getMessage());
            }

            $row = DB::selectOne('select current_schema() as schema');

            $this->assertNotNull($row);
            $this->assertSame('public', $row->schema);
        } finally {
            $tenant->delete();
        }
    }

    public function test_it_does_not_leave_tenant_context_defined_after_failure(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->tenant(status: 'active', schemaName: $this->invalidSchemaName());
        $tenantContext = new TenantContext;
        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(
            TenantExecutionManager::class,
            new TenantExecutionManager($tenantContext, new TenantSearchPathService)
        );

        try {
            try {
                $this->artisan('tenant:reprocess '.$tenant->id)->run();
                $this->fail('Era esperada uma exceção de schema inválido.');
            } catch (InvalidArgumentException) {
            }

            $this->assertNull($tenantContext->get());
        } finally {
            $tenant->delete();
        }
    }

    private function tenant(string $status, string $schemaName): Tenant
    {
        return Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-failure-'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant Failure',
            'schema_name' => $schemaName,
            'status' => $status,
        ]);
    }

    private function invalidSchemaName(): string
    {
        return 'invalid schema '.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
    }
}
