<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Services\Tenant\TenantProvisioningService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class SystemUpdateCommandTest extends TestCase
{
    public function test_it_executes_system_update_steps(): void
    {
        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->artisan('tenants:provision', [
            '--tenant' => 'tenant-dev-001',
            '--create-schema' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->artisan('system:update', [
            '--force' => true,
            '--only-active' => true,
        ])->expectsOutputToContain('Running public migrations')
            ->expectsOutputToContain('Running AdminMenuSeeder')
            ->expectsOutputToContain('Running tenant migrations')
            ->expectsOutputToContain('Validating tenants')
            ->expectsOutputToContain('Clearing optimized cache')
            ->assertSuccessful();
    }

    public function test_it_fails_if_tenant_validation_fails(): void
    {
        Tenant::query()->create([
            'uuid' => fake()->uuid(),
            'code' => 'tenant-dev-001',
            'name' => 'Tenant Dev',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->artisan('system:update', [
            '--force' => true,
            '--skip-tenants' => true,
            '--only-active' => true,
        ])->assertFailed();
    }

    public function test_command_does_not_use_cache_flush(): void
    {
        Cache::shouldReceive('flush')->never();

        $this->artisan('system:update', [
            '--force' => true,
            '--skip-tenants' => true,
            '--skip-validate' => true,
        ])->assertSuccessful();
    }
}
