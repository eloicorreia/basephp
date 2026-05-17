<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\Models\MailConfig;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantPasswordPolicy;
use App\Models\User;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Contracts\Encryption\Encrypter;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminSystemSettingsWebTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_can_view_system_settings_index(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->get(route('admin.system-settings.index', ['tenant' => $tenant->code]))
            ->assertOk()
            ->assertSee('Configurações do Sistema')
            ->assertSee('Senhas')
            ->assertSee('E-mail');
    }

    public function test_user_without_system_settings_permission_cannot_view_module(): void
    {
        $role = $this->createRole('settings-denied', 'Settings Denied');
        $access = Permission::query()->where('code', WebAdminPermissions::ACCESS)->firstOrFail();
        $role->permissions()->sync([$access->id => ['assigned_at' => now()]]);
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web')
            ->get(route('admin.system-settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_password_policy_for_tenant(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.password-policy.update'), [
                'tenant' => $tenant->code,
                'min_length' => 14,
                'max_length' => 120,
                'require_uppercase' => '1',
                'require_lowercase' => '1',
                'require_numbers' => '1',
                'require_symbols' => '1',
                'disallow_common_passwords' => '1',
                'disallow_user_personal_data' => '1',
                'password_expiration_days' => 90,
                'password_history_count' => 6,
                'max_failed_attempts' => 4,
                'lockout_minutes' => 20,
                'must_change_password_on_first_login' => '1',
                'temporary_password_expiration_minutes' => 720,
                'active' => '1',
            ])
            ->assertRedirect(route('admin.system-settings.password-policy.edit', ['tenant' => $tenant->code]));

        $this->assertDatabaseHas('tenant_password_policies', [
            'min_length' => 14,
            'max_length' => 120,
            'password_history_count' => 6,
            'max_failed_attempts' => 4,
        ]);

        $this->assertSame(1, TenantPasswordPolicy::query()->count());
    }

    public function test_admin_can_update_mail_settings_with_encrypted_password(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.mail.update'), [
                'tenant' => $tenant->code,
                'name' => 'SMTP Tenant',
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'smtp-user',
                'password' => 'smtp-secret',
                'from_address' => 'no-reply@example.com',
                'from_name' => 'Tenant',
                'reply_to_address' => 'reply@example.com',
                'reply_to_name' => 'Suporte',
                'timeout_seconds' => 30,
                'verify_peer' => '1',
                'verify_peer_name' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.system-settings.mail.edit', ['tenant' => $tenant->code]));

        $config = MailConfig::query()->where('is_default', true)->firstOrFail();

        $this->assertSame('smtp.example.com', $config->host);
        $this->assertNotSame('smtp-secret', $config->password_encrypted);
        $this->assertSame('smtp-secret', app(Encrypter::class)->decryptString((string) $config->password_encrypted));
    }

    private function adminUser(): User
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');

        return $this->createUser(role: $role);
    }

    private function createSettingsTenant(): Tenant
    {
        return $this->createTenant(
            code: 'settings-tenant',
            name: 'Settings Tenant',
            schemaName: 'public',
        );
    }
}
