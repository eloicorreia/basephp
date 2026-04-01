<?php
declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class TenantExecutionManagerTest extends TestCase
{
    public function test_it_sets_tenant_context_before_running_callback(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->tenant('exec');
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $context = new TenantContext();
        $manager = new TenantExecutionManager($context, new TenantSearchPathService());

        try {
            $manager->run($tenant, function () use ($tenant, $context): void {
                $this->assertSame($tenant->id, $context->require()->id);
                $row = DB::selectOne('select current_schema() as schema');
                $this->assertNotNull($row);
                $this->assertSame($tenant->schema_name, $row->schema);
            });

            $this->assertNull($context->get());
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_restores_public_schema_after_callback_finishes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->tenant('public');
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $manager = new TenantExecutionManager(new TenantContext(), new TenantSearchPathService());

        try {
            $manager->run($tenant, static function (): void {});
            $row = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($row);
            $this->assertSame('public', $row->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_restores_previous_tenant_context_when_execution_is_nested(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $outer = $this->tenant('outer');
        $inner = $this->tenant('inner');
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $outer->schema_name));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $inner->schema_name));

        $context = new TenantContext();
        $manager = new TenantExecutionManager($context, new TenantSearchPathService());

        try {
            $manager->run($outer, function () use ($manager, $context, $outer, $inner): void {
                $manager->run($inner, function () use ($context, $inner): void {
                    $this->assertSame($inner->id, $context->require()->id);
                });

                $this->assertSame($outer->id, $context->require()->id);
            });
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $outer->schema_name));
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $inner->schema_name));
        }
    }

    public function test_it_restores_previous_state_even_when_callback_throws_exception(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->tenant('error');
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $context = new TenantContext();
        $manager = new TenantExecutionManager($context, new TenantSearchPathService());

        try {
            try {
                $manager->run($tenant, static function (): void {
                    throw new RuntimeException('falha simulada');
                });
                $this->fail('Era esperada uma exceção.');
            } catch (RuntimeException $e) {
                $this->assertSame('falha simulada', $e->getMessage());
            }

            $this->assertNull($context->get());
            $row = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($row);
            $this->assertSame('public', $row->schema);
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

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
}
