<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminUserEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_user_index_returns_paginated_data_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $role = $this->createRole(
            'user-list-' . str_replace('-', '', (string) Str::uuid()),
            'User List'
        );

        $listedUser = $this->createUser(role: $role);

        $response = $this->getJson('/api/v1/admin/users', [
            'X-Tenant-Id' => $context['tenant']->code,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados recuperados com sucesso.');

        $data = $response->json('data');

        $this->assertIsArray($data);
        $this->assertContains($listedUser->email, array_column($data, 'email'));
    }

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
            ->assertJsonPath('data.email', $email);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role_id' => $targetRole->id,
        ]);
    }

    public function test_admin_user_show_returns_user_data(): void
    {
        $context = $this->createAdminContext();

        $targetRole = $this->createRole(
            'user-show-' . str_replace('-', '', (string) Str::uuid()),
            'Show User'
        );

        $targetUser = $this->createUser(role: $targetRole);

        $this->getJson('/api/v1/admin/users/' . $targetUser->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $targetUser->id)
            ->assertJsonPath('data.email', $targetUser->email);
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
}