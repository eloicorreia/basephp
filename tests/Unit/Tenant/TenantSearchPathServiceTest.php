<?php
declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Services\Tenant\TenantSearchPathService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TenantSearchPathServiceTest extends TestCase
{
    public function test_it_sets_search_path_to_tenant_schema_and_public(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $service = new TenantSearchPathService();
        $schema = 'tenant_search_path_test';

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schema));

        try {
            $service->setTenantSchema($schema);
            $row = DB::selectOne('show search_path');

            $this->assertNotNull($row);
            $this->assertStringContainsString($schema, (string) $row->search_path);
            $this->assertStringContainsString('public', (string) $row->search_path);
        } finally {
            $service->resetToPublic();
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $schema));
        }
    }

    public function test_it_resets_search_path_to_public(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $service = new TenantSearchPathService();
        $service->resetToPublic();

        $row = DB::selectOne('select current_schema() as schema');
        $this->assertNotNull($row);
        $this->assertSame('public', $row->schema);
    }
}
