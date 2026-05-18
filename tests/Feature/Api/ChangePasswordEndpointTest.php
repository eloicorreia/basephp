<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\RoleCode;
use App\Models\Tenant;
use App\Models\TenantPasswordPolicy;
use App\Models\UserPasswordHistory;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantMigrationService;
use App\Services\Tenant\TenantSchemaService;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class ChangePasswordEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_change_password_requires_authentication(): void
    {
        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'NovaSenha@123',
            'new_password_confirmation' => 'NovaSenha@123',
        ])->assertStatus(401);
    }

    public function test_change_password_returns_validation_error_for_invalid_payload(): void
    {
        $user = $this->createUser(overrides: [
            'password' => 'SenhaAtual@123',
        ]);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => '',
            'new_password' => 'curta',
            'new_password_confirmation' => 'diferente',
        ])->assertStatus(422);
    }

    public function test_change_password_requires_password_change_scope(): void
    {
        $user = $this->createUser(overrides: [
            'password' => 'SenhaAtual@123',
        ]);

        Passport::actingAs($user, ['user.profile']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'NovaSenhaMuitoForte@123',
            'new_password_confirmation' => 'NovaSenhaMuitoForte@123',
        ])->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Acesso negado.',
            'errors' => [],
        ]);
    }

    public function test_change_password_rejects_invalid_current_password(): void
    {
        $user = $this->createUser(overrides: [
            'password' => 'SenhaAtual@123',
            'must_change_password' => true,
        ]);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaErrada@123',
            'new_password' => 'NovaSenhaMuitoForte@123',
            'new_password_confirmation' => 'NovaSenhaMuitoForte@123',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_change_password_updates_password_and_clears_must_change_password(): void
    {
        $user = $this->createUser(overrides: [
            'password' => 'SenhaAtual@123',
            'must_change_password' => true,
        ]);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'NovaSenhaMuitoForte@123',
            'new_password_confirmation' => 'NovaSenhaMuitoForte@123',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Senha alterada com sucesso.',
                'data' => [],
            ]);

        $user->refresh();

        $this->assertTrue(Hash::check('NovaSenhaMuitoForte@123', $user->password));
        $this->assertFalse($user->must_change_password);
    }

    public function test_change_password_with_tenant_header_applies_tenant_policy_and_records_history(): void
    {
        $tenant = $this->createMigratedTenant('tenant_api_change_password_policy', 'api-change-password-policy');
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'name' => 'Api Password',
            'email' => 'api-password@example.com',
            'password' => 'SenhaAtual@123',
        ]);
        $this->grantTenantAccess($user, $tenant, $role);
        $this->seedTenantPasswordPolicy($tenant, [
            'min_length' => 14,
            'max_length' => 40,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'disallow_common_passwords' => true,
            'disallow_user_personal_data' => true,
            'password_history_count' => 3,
        ]);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'senha-fraca',
            'new_password_confirmation' => 'senha-fraca',
        ], ['X-Tenant-Id' => $tenant->code])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'SenhaTenantApi@123',
            'new_password_confirmation' => 'SenhaTenantApi@123',
        ], ['X-Tenant-Id' => $tenant->code])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Senha alterada com sucesso.',
                'data' => [],
            ]);

        $user->refresh();

        $this->assertTrue(Hash::check('SenhaTenantApi@123', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertSame(1, $this->tenantValue($tenant, fn (): int => UserPasswordHistory::query()
            ->where('user_id', $user->id)
            ->count()));
    }

    public function test_change_password_with_tenant_header_denies_user_without_active_membership(): void
    {
        $tenant = $this->createMigratedTenant('tenant_api_change_password_denied', 'api-change-password-denied');
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'password' => 'SenhaAtual@123',
        ]);
        $this->seedTenantPasswordPolicy($tenant);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'SenhaTenantApi@123',
            'new_password_confirmation' => 'SenhaTenantApi@123',
        ], ['X-Tenant-Id' => $tenant->code])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertTrue(Hash::check('SenhaAtual@123', $user->refresh()->password));
    }

    public function test_change_password_without_tenant_header_keeps_global_contract(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'password' => 'SenhaAtual@123',
        ]);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->postJson('/api/v1/auth/change-password', [
            'current_password' => 'SenhaAtual@123',
            'new_password' => 'SenhaGlobal@123',
            'new_password_confirmation' => 'SenhaGlobal@123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('SenhaGlobal@123', $user->refresh()->password));
    }

    private function createMigratedTenant(string $schemaName, string $code): Tenant
    {
        app(TenantSchemaService::class)->createSchema($schemaName);
        app(TenantMigrationService::class)->runTenantMigrations($schemaName, true);

        return $this->createTenant(
            code: $code,
            name: 'Tenant '.$code,
            schemaName: $schemaName,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedTenantPasswordPolicy(Tenant $tenant, array $overrides = []): void
    {
        $this->tenantValue($tenant, function () use ($overrides): void {
            TenantPasswordPolicy::query()->create(array_merge([
                'min_length' => 12,
                'max_length' => 100,
                'require_uppercase' => true,
                'require_lowercase' => true,
                'require_numbers' => true,
                'require_symbols' => true,
                'disallow_common_passwords' => true,
                'disallow_user_personal_data' => true,
                'password_expiration_days' => null,
                'password_history_count' => 5,
                'max_failed_attempts' => 5,
                'lockout_minutes' => 15,
                'must_change_password_on_first_login' => true,
                'temporary_password_expiration_minutes' => 1440,
                'active' => true,
            ], $overrides));
        });
    }

    /**
     * @template TValue
     *
     * @param  callable(): TValue  $callback
     * @return TValue
     */
    private function tenantValue(Tenant $tenant, callable $callback): mixed
    {
        return app(TenantExecutionManager::class)->run($tenant, $callback);
    }
}
