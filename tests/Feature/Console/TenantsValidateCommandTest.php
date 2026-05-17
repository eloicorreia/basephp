<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TenantsValidateCommandTest extends TestCase
{
    public function test_it_returns_success_when_all_required_tables_exist(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->artisan('tenants:provision', [
            '--tenant' => $tenant->code,
            '--create-schema' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('tenants:validate', [
            '--tenant' => $tenant->code,
        ])->expectsTable(
            ['Tenant', 'Schema', 'Schema exists', 'Missing tables', 'Status'],
            [[
                'tenant-dev-001',
                'tenant_dev_001',
                'yes',
                '-',
                'OK',
            ]],
        )->assertSuccessful();
    }

    public function test_it_returns_failure_when_required_table_is_missing(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->artisan('tenants:provision', [
            '--tenant' => $tenant->code,
            '--create-schema' => true,
            '--force' => true,
        ])->assertSuccessful();

        DB::statement('DROP TABLE "tenant_dev_001"."tenant_api_settings" CASCADE');

        $this->artisan('tenants:validate', [
            '--tenant' => $tenant->code,
        ])->expectsOutputToContain('tenant_api_settings')
            ->assertFailed();
    }

    public function test_command_does_not_use_cache_flush(): void
    {
        Cache::shouldReceive('flush')->never();

        $this->artisan('tenants:validate', [
            '--only-active' => true,
        ])->assertSuccessful();
    }
}
