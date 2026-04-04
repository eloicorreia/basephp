<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\EmailDispatch;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminEmailRetryEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_email_retry_requeues_failed_dispatch(): void
    {
        Queue::fake();

        $context = $this->createAdminContext();

        $dispatch = EmailDispatch::query()->create([
            'queue' => 'notifications',
            'to_recipients' => ['destinatario@example.com'],
            'subject' => 'Assunto teste',
            'body' => 'Corpo teste',
            'is_html' => false,
            'status' => 'failed',
            'error_message' => 'SMTP timeout',
        ]);

        $this->postJson('/api/v1/admin/emails/' . $dispatch->id . '/retry', [], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(202)
            ->assertJsonPath('success', true);

        $dispatch->refresh();

        $this->assertSame('queued', $dispatch->status);
        $this->assertNull($dispatch->error_message);
    }

    /**
     * @return array<string, mixed>
     */
    private function createAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = $this->createRole('admin', 'Administrator');

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