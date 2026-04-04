<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Job;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminQueueJobsEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_queue_jobs_index_returns_paginated_jobs(): void
    {
        $context = $this->createAdminContext();

        $job = Job::query()->create([
            'queue' => 'notifications',
            'payload' => json_encode([
                'displayName' => 'App\Jobs\SendEmailJob',
                'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $response = $this->getJson('/api/v1/admin/queues/jobs?queue=notifications', [
            'X-Tenant-Id' => $context['tenant']->code,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.page', 1);

        $data = $response->json('data');

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $ids = array_column($data, 'id');

        $this->assertContains($job->id, $ids);
    }

    public function test_queue_jobs_show_returns_job_details(): void
    {
        $context = $this->createAdminContext();

        $job = Job::query()->create([
            'queue' => 'notifications',
            'payload' => json_encode([
                'displayName' => 'App\Jobs\SendEmailJob',
                'job' => 'Illuminate\Queue\CallQueuedHandler@call',
            ]),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->getJson('/api/v1/admin/queues/jobs/' . $job->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $job->id)
            ->assertJsonPath('data.queue', 'notifications');
    }

    /**
     * @return array<string, mixed>
     */
    private function createAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'admin'],
            [
                'name' => 'Administrator',
                'active' => true,
            ]
        );

        $tenantRole = $this->createRole(
            'tenant-admin-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant Admin'
        );

        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }
}