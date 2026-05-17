<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Services\Tenant\TenantMigrationService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class TenantsMigrateCommandTest extends TestCase
{
    public function test_it_migrates_specific_tenant(): void
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

        $this->artisan('tenants:migrate', [
            '--tenant' => $tenant->code,
            '--force' => true,
        ])->expectsOutputToContain('Migrating tenant: tenant-dev-001 [tenant_dev_001]')
            ->assertSuccessful();
    }

    public function test_it_fails_when_tenant_does_not_exist(): void
    {
        $this->artisan('tenants:migrate', [
            '--tenant' => 'tenant-not-found',
        ])->expectsOutputToContain('Tenant não encontrado: tenant-not-found')
            ->assertFailed();
    }

    public function test_it_fails_when_schema_does_not_exist(): void
    {
        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->artisan('tenants:migrate', [
            '--tenant' => 'tenant-dev-001',
            '--force' => true,
        ])->expectsOutputToContain('FAILED: Schema não existe.')
            ->assertFailed();
    }

    public function test_only_active_ignores_inactive_tenant(): void
    {
        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-inactive-001',
            'name' => 'Tenant Inactive',
            'schema_name' => 'tenant_inactive_001',
            'status' => Tenant::STATUS_INACTIVE,
        ]);

        $migrationService = Mockery::mock(TenantMigrationService::class);
        $migrationService->shouldNotReceive('runTenantMigrations');

        $this->app->instance(TenantMigrationService::class, $migrationService);

        $this->artisan('tenants:migrate', [
            '--only-active' => true,
            '--force' => true,
        ])->assertSuccessful();
    }

    public function test_command_does_not_use_cache_flush(): void
    {
        Cache::shouldReceive('flush')->never();

        $this->artisan('tenants:migrate', [
            '--only-active' => true,
            '--force' => true,
        ])->assertSuccessful();
    }
}
