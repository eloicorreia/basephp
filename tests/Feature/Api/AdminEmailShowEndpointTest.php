<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\EmailDispatch;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminEmailShowEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_email_show_returns_dispatch_details(): void
    {
        $context = $this->createAdminContext();

        $dispatch = EmailDispatch::query()->create([
            'queue' => 'notifications',
            'to_recipients' => ['destinatario@example.com'],
            'subject' => 'Assunto teste',
            'body' => 'Corpo teste',
            'is_html' => false,
            'status' => 'queued',
        ]);

        $this->getJson('/api/v1/admin/emails/' . $dispatch->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $dispatch->id)
            ->assertJsonPath('data.subject', 'Assunto teste');
    }

    private function createAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = \App\Models\Role::query()->firstOrCreate(
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
