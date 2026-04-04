<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\EmailDispatch;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminEmailIndexEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_email_index_returns_paginated_data(): void
    {
        $context = $this->createAdminContext();

        EmailDispatch::query()->create([
            'queue' => 'notifications',
            'to_recipients' => ['destinatario@example.com'],
            'subject' => 'Assunto 1',
            'body' => 'Corpo 1',
            'is_html' => false,
            'status' => 'queued',
        ]);

        $this->getJson('/api/v1/admin/emails', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.page', 1);
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