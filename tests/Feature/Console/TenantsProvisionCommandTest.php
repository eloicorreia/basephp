<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TenantsProvisionCommandTest extends TestCase
{
    public function test_it_creates_schema_when_create_schema_is_informed(): void
    {
        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_PROVISIONING,
        ]);

        $this->artisan('tenants:provision', [
            '--tenant' => 'tenant-dev-001',
            '--create-schema' => true,
            '--force' => true,
        ])->expectsOutputToContain('Provisioning tenant: tenant-dev-001 [tenant_dev_001]')
            ->assertSuccessful();

        $row = DB::selectOne(
            'SELECT EXISTS (SELECT 1 FROM information_schema.schemata WHERE schema_name = ?) AS schema_exists',
            ['tenant_dev_001'],
        );

        $this->assertTrue(filter_var($row?->schema_exists, FILTER_VALIDATE_BOOLEAN));
    }

    public function test_it_fails_when_schema_does_not_exist_and_create_schema_was_not_informed(): void
    {
        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_PROVISIONING,
        ]);

        $this->artisan('tenants:provision', [
            '--tenant' => 'tenant-dev-001',
            '--force' => true,
        ])->expectsOutputToContain('FAILED: Schema não existe. Informe --create-schema para criar o schema antes das migrations.')
            ->assertFailed();
    }

    public function test_it_runs_migrations_and_validates_required_tables(): void
    {
        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_PROVISIONING,
        ]);

        $this->artisan('tenants:provision', [
            '--tenant' => 'tenant-dev-001',
            '--create-schema' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('tenants:validate', [
            '--tenant' => 'tenant-dev-001',
        ])->assertSuccessful();
    }

    public function test_command_does_not_use_cache_flush(): void
    {
        Cache::shouldReceive('flush')->never();

        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_PROVISIONING,
        ]);

        $this->artisan('tenants:provision', [
            '--tenant' => 'tenant-dev-001',
        ])->assertFailed();
    }
}
