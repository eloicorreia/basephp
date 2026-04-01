<?php
declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class SearchPathCleanupIntegrationTest extends TestCase
{
    private function tenant(string $prefix): Tenant
    {
        return Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-' . $prefix . '-' . str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant ' . ucfirst($prefix),
            'schema_name' => 'tenant_' . $prefix . '_' . str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);
    }

    public function test_it_switches_to_tenant_schema_and_returns_to_public_after_execution(): void
    {
        if (DB::getDriverName() !== 'pgsql') $this->markTestSkipped('Este teste requer PostgreSQL.');

        $tenant = $this->tenant('cleanup');
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));
        $manager = new TenantExecutionManager(new TenantContext(), new TenantSearchPathService());

        try {
            $manager->run($tenant, function () use ($tenant): void {
                $row = DB::selectOne('select current_schema() as schema');
                $this->assertSame($tenant->schema_name, $row->schema);
            });

            $row = DB::selectOne('select current_schema() as schema');
            $this->assertSame('public', $row->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_restores_previous_tenant_schema_when_execution_is_nested(): void
    {
        if (DB::getDriverName() !== 'pgsql') $this->markTestSkipped('Este teste requer PostgreSQL.');
        $outer = $this->tenant('outer'); $inner = $this->tenant('inner');
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $outer->schema_name));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $inner->schema_name));
        $context = new TenantContext();
        $manager = new TenantExecutionManager($context, new TenantSearchPathService());

        try {
            $manager->run($outer, function () use ($manager, $inner, $outer): void {
                $manager->run($inner, static function (): void {});
                $row = DB::selectOne('select current_schema() as schema');
                $this->assertSame($outer->schema_name, $row->schema);
            });
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $outer->schema_name));
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $inner->schema_name));
        }
    }

    public function test_it_restores_public_schema_even_when_inner_execution_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') $this->markTestSkipped('Este teste requer PostgreSQL.');
        $tenant = $this->tenant('failure');
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));
        $manager = new TenantExecutionManager(new TenantContext(), new TenantSearchPathService());

        try {
            try {
                $manager->run($tenant, static function (): void { throw new RuntimeException('falha'); });
            } catch (RuntimeException $e) {
                $this->assertSame('falha', $e->getMessage());
            }

            $row = DB::selectOne('select current_schema() as schema');
            $this->assertSame('public', $row->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }
}
