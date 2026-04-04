<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AuthMeEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_auth_me_returns_authenticated_user_data_for_valid_tenant_context(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $userRole = $this->createRole(
            'user-' . str_replace('-', '', (string) Str::uuid()),
            'User'
        );

        $tenantRole = $this->createRole(
            'tenant-user-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $user = $this->createUser(role: $userRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados recuperados com sucesso.')
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertHeader('X-Request-Id')
            ->assertHeader('X-Trace-Id');
    }

    public function test_auth_me_returns_standard_forbidden_response_when_membership_is_invalid(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $userRole = $this->createRole(
            'user-' . str_replace('-', '', (string) Str::uuid()),
            'User'
        );

        $user = $this->createUser(role: $userRole);

        Passport::actingAs($user, ['user.profile']);

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }
}