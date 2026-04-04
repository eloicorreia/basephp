<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Job;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminQueueSummaryEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_queue_summary_returns_counts(): void
    {
        $context = $this->createAdminContext();
        $queueName = 'notifications-' . str_replace('-', '', (string) Str::uuid());

        Job::query()->create([
            'queue' => $queueName,
            'payload' => json_encode(['displayName' => 'Job A']),
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        Job::query()->create([
            'queue' => $queueName,
            'payload' => json_encode(['displayName' => 'Job B']),
            'attempts' => 1,
            'reserved_at' => now()->timestamp,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);

        $this->getJson('/api/v1/admin/queues/summary?queue=' . $queueName, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queue', $queueName)
            ->assertJsonPath('data.pending_jobs', 1)
            ->assertJsonPath('data.running_jobs', 1);
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

        return ['tenant' => $tenant, 'user' => $user];
    }
}