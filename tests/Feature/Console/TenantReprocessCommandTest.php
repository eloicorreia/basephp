<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenantReprocessCommandTest extends TestCase
{
    public function test_it_fails_when_no_tenant_id_and_no_all_option_are_provided(): void
    {
        $this->artisan('tenant:reprocess')
            ->expectsOutput('Informe {tenant_id} ou utilize --all.')
            ->assertFailed();
    }

    public function test_it_processes_only_active_tenants_when_all_option_is_used(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $activeA = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-a-' . str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant A',
            'schema_name' => 'tenant_a_' . str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);

        $inactive = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-b-' . str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant B',
            'schema_name' => 'tenant_b_' . str_replace('-', '', (string) Str::uuid()),
            'status' => 'inactive',
        ]);

        $activeC = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-c-' . str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant C',
            'schema_name' => 'tenant_c_' . str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $activeA->schema_name));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $activeC->schema_name));

        $tenantContext = new TenantContext();
        $searchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        try {
            $this->artisan('tenant:reprocess --all')
                ->expectsOutput("Processando tenant {$activeA->id} no schema {$activeA->schema_name}")
                ->expectsOutput("Processando tenant {$activeC->id} no schema {$activeC->schema_name}")
                ->assertSuccessful();

            $this->assertDatabaseHas('tenants', [
                'id' => $inactive->id,
                'status' => 'inactive',
            ]);

            $currentSchema = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($currentSchema);
            $this->assertSame('public', $currentSchema->schema);

            $this->assertNull($tenantContext->get());
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $activeA->schema_name));
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $activeC->schema_name));
        }
    }

    public function test_it_processes_a_single_tenant_when_tenant_id_is_provided(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main-' . str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main_' . str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $tenantContext = new TenantContext();
        $searchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        try {
            $this->artisan('tenant:reprocess ' . $tenant->id)
                ->expectsOutput("Processando tenant {$tenant->id} no schema {$tenant->schema_name}")
                ->assertSuccessful();

            $currentSchema = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($currentSchema);
            $this->assertSame('public', $currentSchema->schema);

            $this->assertNull($tenantContext->get());
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }
}