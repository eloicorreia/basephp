<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminFailedJobEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_failed_jobs_index_returns_paginated_data(): void
    {
        $context = $this->createAdminContext();

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'notifications',
            'payload' => json_encode(['displayName' => 'App\Jobs\SendEmailJob']),
            'exception' => 'RuntimeException: failure',
            'failed_at' => now(),
        ]);

        $this->getJson('/api/v1/admin/queues/failed-jobs', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.page', 1);
    }

    public function test_failed_job_show_returns_details(): void
    {
        $context = $this->createAdminContext();

        $id = DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'notifications',
            'payload' => json_encode(['displayName' => 'App\Jobs\SendEmailJob']),
            'exception' => 'RuntimeException: failure',
            'failed_at' => now(),
        ]);

        $this->getJson('/api/v1/admin/queues/failed-jobs/' . $id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $id);
    }

    private function createAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = $this->createRole(
            'admin-' . str_replace('-', '', (string) Str::uuid()),
            'Administrator'
        );

        $tenantRole = $this->createRole(
            'tenant-admin-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant Admin'
        );

        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        return ['tenant' => $tenant, 'user' => $user];
    }
}