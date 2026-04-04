<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminUserStoreEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_user_store_creates_user_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $targetRole = $this->createRole(
            'user-created-' . str_replace('-', '', (string) Str::uuid()),
            'Created User'
        );

        $email = 'user.' . str_replace('-', '', (string) Str::uuid()) . '@example.com';

        $this->postJson('/api/v1/admin/users', [
            'name' => 'Usuário de Teste',
            'email' => $email,
            'password' => 'SenhaForteMuitoBoa@123',
            'password_confirmation' => 'SenhaForteMuitoBoa@123',
            'role_id' => $targetRole->id,
            'is_active' => true,
            'must_change_password' => false,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuário criado com sucesso.')
            ->assertJsonPath('data.email', $email)
            ->assertJsonPath('data.role.id', $targetRole->id);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role_id' => $targetRole->id,
        ]);
    }

    public function test_admin_user_store_returns_validation_error_for_invalid_payload(): void
    {
        $context = $this->createAdminContext();

        $this->postJson('/api/v1/admin/users', [
            'name' => '',
            'email' => 'email-invalido',
            'password' => 'curta',
            'password_confirmation' => 'diferente',
            'role_id' => 999999999,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertStatus(422);
    }

    public function test_admin_user_store_returns_forbidden_for_non_admin_user(): void
    {
        $context = $this->createNonAdminContext();

        $targetRole = $this->createRole(
            'user-blocked-' . str_replace('-', '', (string) Str::uuid()),
            'Blocked User'
        );

        $this->postJson('/api/v1/admin/users', [
            'name' => 'Usuário Bloqueado',
            'email' => 'blocked.' . str_replace('-', '', (string) Str::uuid()) . '@example.com',
            'password' => 'SenhaForteMuitoBoa@123',
            'password_confirmation' => 'SenhaForteMuitoBoa@123',
            'role_id' => $targetRole->id,
            'is_active' => true,
            'must_change_password' => false,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /**
     * @return array<string, mixed>
     */
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

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createNonAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tenant-user-' . str_replace('-', '', (string) Str::uuid())
        );

        $userRole = $this->createRole(
            'user-' . str_replace('-', '', (string) Str::uuid()),
            'User'
        );

        $tenantRole = $this->createRole(
            'tenant-user-role-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $user = $this->createUser(role: $userRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }
}