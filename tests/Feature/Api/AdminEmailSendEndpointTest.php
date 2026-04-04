<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Role;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminEmailSendEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_email_send_queues_dispatch_record(): void
    {
        Queue::fake();

        $context = $this->createAdminContext();

        $response = $this->postJson('/api/v1/admin/emails/send', [
            'to' => ['destinatario@gmail.com'],
            'subject' => 'Assunto teste',
            'body' => 'Corpo teste',
            'is_html' => false,
            'queue' => 'notifications',
            'external_reference' => 'MAIL-001',
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ]);

        $response
            ->assertStatus(202)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('email_dispatches', [
            'subject' => 'Assunto teste',
            'queue' => 'notifications',
            'external_reference' => 'MAIL-001',
            'status' => 'queued',
        ]);
    }

    public function test_email_send_returns_validation_error_for_invalid_payload(): void
    {
        Queue::fake();

        $context = $this->createAdminContext();

        $this->postJson('/api/v1/admin/emails/send', [
            'to' => ['email-invalido'],
            'subject' => '',
            'body' => '',
            'queue' => 'fila-inexistente',
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertStatus(422);
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