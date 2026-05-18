<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantPasswordPolicy;
use App\Models\TenantSecuritySetting;
use App\Models\TenantUserSecurityState;
use App\Models\TenantUserWebSession;
use App\Models\User;
use App\Models\UserPasswordHistory;
use App\Services\Admin\Web\AdminWebAuditService;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantMigrationService;
use App\Services\Tenant\TenantSchemaService;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminWebAuthenticationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_login_page_uses_official_template_master_assets(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Módulo web administrativo')
            ->assertSee('vendor/templateweb/master/assets/css/bootstrap.min.css', false)
            ->assertSee('vendor/templateweb/master/assets/css/icons.min.css', false)
            ->assertSee('vendor/templateweb/master/assets/css/app.min.css', false)
            ->assertSee('vendor/templateweb/master/assets/css/admin-contract.css', false)
            ->assertSee('vendor/templateweb/master/assets/js/pages/password-addon.init.js', false)
            ->assertSee('Tenant')
            ->assertSee('Esqueci minha senha');
    }

    public function test_admin_login_requires_tenant_context(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'admin-web@example.com',
            'password' => 'secret-password',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin-web@example.com',
            'password' => 'secret-password',
        ])->assertSessionHasErrors('tenant_code');

        $this->assertGuest('web');
        $this->assertNull($user->refresh()->last_login_at);
    }

    public function test_admin_user_can_login_with_tenant_header_and_active_policy(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_login_active', 'web-login-active');

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ], ['X-Tenant-Id' => $tenant->code])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame($tenant->code, session('admin_tenant_code'));
        $this->assertSame(0, (int) TenantUserSecurityState::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->value('failed_login_attempts'));
    }

    public function test_admin_user_can_login_with_tenant_code_form_field(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_login_form', 'web-login-form');

        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertSame($tenant->code, session('admin_tenant_code'));
    }

    public function test_tenant_login_denies_invalid_or_inactive_tenant(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'invalid-tenant-admin@example.com',
            'password' => 'SenhaAtual@123',
        ]);
        $inactiveTenant = $this->createTenant(code: 'inactive-web-login', isActive: false);

        foreach (['missing-web-login', $inactiveTenant->code] as $tenantCode) {
            $this->post('/admin/login', [
                'tenant_code' => $tenantCode,
                'email' => $user->email,
                'password' => 'SenhaAtual@123',
            ])->assertSessionHasErrors('email');

            $this->assertGuest('web');
        }
    }

    public function test_tenant_login_denies_user_without_active_membership(): void
    {
        $tenant = $this->createMigratedTenant('tenant_web_login_no_membership', 'web-login-no-membership');
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'no-membership-admin@example.com',
            'password' => 'SenhaAtual@123',
        ]);
        $this->seedTenantLoginSettings($tenant);

        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_tenant_login_denies_inactive_user_without_authenticating(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_login_inactive_user', 'web-login-inactive-user');
        $user->forceFill(['is_active' => false])->save();

        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_tenant_login_denies_user_without_admin_permission(): void
    {
        $tenant = $this->createMigratedTenant('tenant_web_login_no_admin', 'web-login-no-admin');
        $role = $this->createRole(RoleCode::USUARIO->value, 'Usuário');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'no-admin-web@example.com',
            'password' => 'SenhaAtual@123',
            'password_changed_at' => now(),
        ]);
        $this->grantTenantAccess($user, $tenant, $role);
        $this->seedTenantLoginSettings($tenant);

        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_tenant_login_denies_disallowed_ip(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_login_ip_denied', 'web-login-ip-denied', securityOverrides: [
            'allowed_ip_ranges' => ['198.51.100.0/24'],
        ]);

        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ], ['REMOTE_ADDR' => '203.0.113.10'])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_failed_tenant_login_increments_counter_only_for_current_tenant(): void
    {
        [$tenantA, $user, $role] = $this->tenantLoginFixture('tenant_web_login_fail_a', 'web-login-fail-a');
        $tenantB = $this->createMigratedTenant('tenant_web_login_fail_b', 'web-login-fail-b');
        $this->grantTenantAccess($user, $tenantB, $role);
        $this->seedTenantLoginSettings($tenantB);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'SenhaErrada@123',
        ], ['X-Tenant-Id' => $tenantA->code])->assertSessionHasErrors('email');

        $this->assertSame(1, (int) TenantUserSecurityState::query()
            ->where('tenant_id', $tenantA->id)
            ->where('user_id', $user->id)
            ->value('failed_login_attempts'));
        $this->assertFalse(TenantUserSecurityState::query()
            ->where('tenant_id', $tenantB->id)
            ->where('user_id', $user->id)
            ->exists());
    }

    public function test_tenant_login_locks_after_max_attempts_without_blocking_other_tenant(): void
    {
        [$tenantA, $user, $role] = $this->tenantLoginFixture('tenant_web_login_lock_a', 'web-login-lock-a', securityOverrides: [
            'max_login_attempts' => 2,
            'lockout_duration_minutes' => 30,
        ]);
        $tenantB = $this->createMigratedTenant('tenant_web_login_lock_b', 'web-login-lock-b');
        $this->grantTenantAccess($user, $tenantB, $role);
        $this->seedTenantLoginSettings($tenantB, securityOverrides: [
            'max_login_attempts' => 2,
            'lockout_duration_minutes' => 30,
        ]);

        foreach ([1, 2] as $_) {
            $this->post('/admin/login', [
                'email' => $user->email,
                'password' => 'SenhaErrada@123',
            ], ['X-Tenant-Id' => $tenantA->code])->assertSessionHasErrors('email');
        }

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ], ['X-Tenant-Id' => $tenantA->code])->assertSessionHasErrors('email');

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ], ['X-Tenant-Id' => $tenantB->code])->assertRedirect(route('admin.dashboard'));
    }

    public function test_tenant_login_success_resets_counter_only_for_current_tenant(): void
    {
        [$tenantA, $user, $role] = $this->tenantLoginFixture('tenant_web_login_reset_a', 'web-login-reset-a');
        $tenantB = $this->createMigratedTenant('tenant_web_login_reset_b', 'web-login-reset-b');
        $this->grantTenantAccess($user, $tenantB, $role);
        $this->seedTenantLoginSettings($tenantB);

        TenantUserSecurityState::query()->create([
            'tenant_id' => $tenantA->id,
            'user_id' => $user->id,
            'failed_login_attempts' => 2,
        ]);
        TenantUserSecurityState::query()->create([
            'tenant_id' => $tenantB->id,
            'user_id' => $user->id,
            'failed_login_attempts' => 3,
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ], ['X-Tenant-Id' => $tenantA->code])->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, (int) TenantUserSecurityState::query()
            ->where('tenant_id', $tenantA->id)
            ->where('user_id', $user->id)
            ->value('failed_login_attempts'));
        $this->assertSame(3, (int) TenantUserSecurityState::query()
            ->where('tenant_id', $tenantB->id)
            ->where('user_id', $user->id)
            ->value('failed_login_attempts'));
    }

    public function test_tenant_login_forces_change_on_first_login_and_expired_password(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_login_expired', 'web-login-expired', passwordOverrides: [
            'password_expiration_days' => 1,
            'must_change_password_on_first_login' => true,
        ]);
        $user->forceFill(['password_changed_at' => now()->subDays(2)])->save();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ], ['X-Tenant-Id' => $tenant->code])->assertRedirect(route('admin.password.change'));

        $this->assertTrue((bool) $user->refresh()->must_change_password);
    }

    public function test_tenant_login_rejects_expired_temporary_password(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_login_temp_expired', 'web-login-temp-expired', passwordOverrides: [
            'temporary_password_expiration_minutes' => 5,
        ]);
        $user->forceFill([
            'must_change_password' => true,
            'password_changed_at' => null,
            'created_at' => now()->subMinutes(10),
        ])->save();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ], ['X-Tenant-Id' => $tenant->code])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_non_admin_user_cannot_login_to_admin_web_module(): void
    {
        $tenant = $this->createMigratedTenant('tenant_web_login_non_admin', 'web-login-non-admin');
        $role = $this->createRole(RoleCode::USUARIO->value, 'Usuário');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'user-web@example.com',
            'password' => 'SenhaAtual@123',
            'password_changed_at' => now(),
        ]);
        $this->grantTenantAccess($user, $tenant, $role);
        $this->seedTenantLoginSettings($tenant);

        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => 'user-web@example.com',
            'password' => 'SenhaAtual@123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::PERMISSION_DENIED,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_failed_admin_login_is_audited(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_failed_admin_web', 'failed-admin-web', passwordOverrides: [
            'disallow_user_personal_data' => false,
        ]);
        $user->forceFill([
            'email' => 'failed-admin-web@example.com',
        ])->save();

        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => 'failed-admin-web@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::LOGIN_FAILED,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_logout_is_audited(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web')
            ->post(route('admin.logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::LOGOUT,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_with_must_change_password_is_forced_to_change_password(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, mustChangePassword: true);

        $this->actingAs($user, 'web')
            ->get('/admin')
            ->assertRedirect(route('admin.password.change'));

        $this->actingAs($user, 'web')
            ->get(route('admin.password.change'))
            ->assertOk()
            ->assertSee('Trocar senha');
    }

    public function test_user_without_must_change_password_accesses_admin_normally(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, mustChangePassword: false);

        $this->actingAs($user, 'web')
            ->get('/admin')
            ->assertOk();
    }

    public function test_admin_can_change_password_from_user_menu(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, mustChangePassword: true, overrides: [
            'password' => 'SenhaAtual@123',
        ]);

        $this->actingAs($user, 'web')
            ->get(route('admin.password.change'))
            ->assertOk();

        $this->actingAs($user, 'web')
            ->put(route('admin.password.update'), [
                'current_password' => 'SenhaAtual@123',
                'new_password' => 'NovaSenhaAdmin@123',
                'new_password_confirmation' => 'NovaSenhaAdmin@123',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $user->refresh();

        $this->assertTrue(Hash::check('NovaSenhaAdmin@123', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);

        $this->actingAs($user, 'web')
            ->get('/admin')
            ->assertSee('Trocar senha');
    }

    public function test_tenant_password_change_validates_policy_requirements(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_pwd_policy', 'web-pwd-policy', passwordOverrides: [
            'min_length' => 12,
            'max_length' => 20,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'disallow_common_passwords' => true,
            'disallow_user_personal_data' => true,
        ]);

        $this->withSession(['admin_tenant_code' => $tenant->code])
            ->actingAs($user, 'web')
            ->put(route('admin.password.update'), [
                'current_password' => 'SenhaAtual@123',
                'new_password' => 'fraca',
                'new_password_confirmation' => 'fraca',
            ])
            ->assertSessionHasErrors('new_password');

        $this->withSession(['admin_tenant_code' => $tenant->code])
            ->actingAs($user, 'web')
            ->put(route('admin.password.update'), [
                'current_password' => 'SenhaAtual@123',
                'new_password' => 'SenhaMuitoGrandeParaPolicy@123',
                'new_password_confirmation' => 'SenhaMuitoGrandeParaPolicy@123',
            ])
            ->assertSessionHasErrors('new_password');

        $this->withSession(['admin_tenant_code' => $tenant->code])
            ->actingAs($user, 'web')
            ->put(route('admin.password.update'), [
                'current_password' => 'SenhaAtual@123',
                'new_password' => 'password',
                'new_password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('new_password');

        $this->withSession(['admin_tenant_code' => $tenant->code])
            ->actingAs($user, 'web')
            ->put(route('admin.password.update'), [
                'current_password' => 'SenhaAtual@123',
                'new_password' => 'AdminWeb@123',
                'new_password_confirmation' => 'AdminWeb@123',
            ])
            ->assertSessionHasErrors('new_password');
    }

    public function test_tenant_password_change_respects_history_and_records_success(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_pwd_history', 'web-pwd-history', passwordOverrides: [
            'password_history_count' => 2,
        ]);

        $this->tenantValue($tenant, fn () => UserPasswordHistory::query()->create([
            'user_id' => $user->id,
            'password_hash' => bcrypt('SenhaAntiga@123'),
        ]));

        $this->withSession(['admin_tenant_code' => $tenant->code])
            ->actingAs($user, 'web')
            ->put(route('admin.password.update'), [
                'current_password' => 'SenhaAtual@123',
                'new_password' => 'SenhaAntiga@123',
                'new_password_confirmation' => 'SenhaAntiga@123',
            ])
            ->assertSessionHasErrors('new_password');

        $this->withSession(['admin_tenant_code' => $tenant->code])
            ->actingAs($user, 'web')
            ->put(route('admin.password.update'), [
                'current_password' => 'SenhaAtual@123',
                'new_password' => 'SenhaNova@123',
                'new_password_confirmation' => 'SenhaNova@123',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $user->refresh();

        $this->assertFalse($user->must_change_password);
        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('SenhaNova@123', $user->password));
        $this->assertSame(2, $this->tenantValue($tenant, fn (): int => UserPasswordHistory::query()->where('user_id', $user->id)->count()));
    }

    public function test_web_session_lifetime_expires_tenant_admin_session(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_session_lifetime', 'web-session-lifetime', securityOverrides: [
            'session_lifetime_minutes' => 1,
        ]);
        $this->loginTenantWebUser($tenant, $user);

        session(['admin_login_at' => now()->subMinutes(2)->timestamp]);

        $this->get('/admin')
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_web_session_idle_timeout_expires_tenant_admin_session(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_session_idle', 'web-session-idle', securityOverrides: [
            'idle_timeout_minutes' => 1,
        ]);
        $this->loginTenantWebUser($tenant, $user);

        TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->update(['last_activity_at' => now()->subMinutes(2)]);

        $this->get('/admin')
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_force_single_session_revokes_previous_tenant_web_session(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_session_single', 'web-session-single', securityOverrides: [
            'force_single_session_per_user' => true,
        ]);

        TenantUserWebSession::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'session_id' => 'older-session',
            'ip_address' => '127.0.0.1',
            'last_activity_at' => now(),
        ]);

        $this->loginTenantWebUser($tenant, $user);

        $this->assertNotNull(TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('session_id', 'older-session')
            ->value('revoked_at'));
    }

    public function test_revoked_tenant_web_session_is_denied(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_session_revoked', 'web-session-revoked');
        $this->loginTenantWebUser($tenant, $user);

        TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->update([
                'revoked_at' => now(),
                'revoked_reason' => 'test',
            ]);

        $this->get('/admin')
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_ip_blocked_after_login_denies_tenant_web_session(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_session_ip', 'web-session-ip');
        $this->loginTenantWebUser($tenant, $user);

        $this->tenantValue($tenant, fn () => TenantSecuritySetting::query()->update([
            'allowed_ip_ranges' => ['198.51.100.0/24'],
        ]));

        $this->get('/admin', ['REMOTE_ADDR' => '203.0.113.10'])
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_tenant_logout_revokes_web_session_and_clears_tenant_session(): void
    {
        [$tenant, $user] = $this->tenantLoginFixture('tenant_web_session_logout', 'web-session-logout');
        $this->loginTenantWebUser($tenant, $user);

        $this->post(route('admin.logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
        $this->assertNotNull(TenantUserWebSession::query()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->value('revoked_at'));
    }

    public function test_authenticated_non_admin_denial_is_audited(): void
    {
        $role = $this->createRole(RoleCode::USUARIO->value, 'Usuário');
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web')
            ->get('/admin')
            ->assertForbidden();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::PERMISSION_DENIED,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_web_session_does_not_authenticate_api_guard(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $tenant = $this->createTenant();
        $user = $this->createUser(role: $role);
        $this->grantTenantAccess($user, $tenant, $role);

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/auth/me', ['X-Tenant-Id' => $tenant->code])
            ->assertUnauthorized();
    }

    public function test_passport_token_does_not_authenticate_admin_web_module(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        Passport::actingAs($user, ['admin.full']);

        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_admin_forgot_password_sends_reset_notification_and_audits_request(): void
    {
        Notification::fake();

        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'admin-reset@example.com',
        ]);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Recuperar senha')
            ->assertSee('vendor/templateweb/master/assets/css/icons.min.css', false);

        $this->post(route('password.email'), [
            'email' => 'admin-reset@example.com',
        ])->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::PASSWORD_RESET_REQUESTED,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_forgot_password_does_not_send_reset_notification_to_non_admin_user(): void
    {
        Notification::fake();

        $role = $this->createRole(RoleCode::USUARIO->value, 'Usuário');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'regular-reset@example.com',
        ]);

        $this->post(route('password.email'), [
            'email' => 'regular-reset@example.com',
        ])->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::PASSWORD_RESET_REQUESTED,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_admin_reset_password_updates_admin_password_and_audits_success(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, mustChangePassword: true, overrides: [
            'email' => 'admin-password-update@example.com',
            'password' => 'old-admin-password',
        ]);

        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Redefinir senha')
            ->assertSee('vendor/templateweb/master/assets/js/pages/password-addon.init.js', false);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenhaAdmin123!',
            'password_confirmation' => 'NovaSenhaAdmin123!',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertTrue(Hash::check('NovaSenhaAdmin123!', $user->password));
        $this->assertFalse($user->must_change_password);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::PASSWORD_RESET_SUCCEEDED,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_reset_password_rejects_valid_token_from_non_admin_user(): void
    {
        $role = $this->createRole(RoleCode::USUARIO->value, 'Usuário');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'regular-password-update@example.com',
            'password' => 'old-user-password',
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NovaSenhaAdmin123!',
            'password_confirmation' => 'NovaSenhaAdmin123!',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-user-password', $user->refresh()->password));
    }

    /**
     * @param  array<string, mixed>  $securityOverrides
     * @param  array<string, mixed>  $passwordOverrides
     * @return array{0: Tenant, 1: User, 2: Role}
     */
    private function tenantLoginFixture(
        string $schemaName,
        string $code,
        array $securityOverrides = [],
        array $passwordOverrides = []
    ): array {
        $tenant = $this->createMigratedTenant($schemaName, $code);
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'name' => 'Admin Web',
            'email' => $code.'@example.com',
            'password' => 'SenhaAtual@123',
            'password_changed_at' => now(),
            'must_change_password' => false,
        ]);

        $this->grantTenantAccess($user, $tenant, $role);
        $this->seedTenantLoginSettings($tenant, $securityOverrides, $passwordOverrides);

        return [$tenant, $user, $role];
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
     * @param  array<string, mixed>  $securityOverrides
     * @param  array<string, mixed>  $passwordOverrides
     */
    private function seedTenantLoginSettings(Tenant $tenant, array $securityOverrides = [], array $passwordOverrides = []): void
    {
        $this->tenantValue($tenant, function () use ($securityOverrides, $passwordOverrides): void {
            TenantSecuritySetting::query()->create(array_merge([
                'session_lifetime_minutes' => 120,
                'idle_timeout_minutes' => null,
                'force_single_session_per_user' => false,
                'logout_on_password_change' => true,
                'max_login_attempts' => 5,
                'lockout_duration_minutes' => 15,
                'unlock_requires_admin' => false,
                'notify_user_on_failed_login' => true,
                'notify_admin_on_lockout' => true,
                'allowed_ip_ranges' => null,
            ], $securityOverrides));

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
            ], $passwordOverrides));
        });
    }

    private function loginTenantWebUser(Tenant $tenant, User $user): void
    {
        $this->post('/admin/login', [
            'tenant_code' => $tenant->code,
            'email' => $user->email,
            'password' => 'SenhaAtual@123',
        ])->assertRedirect(route('admin.dashboard'));
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
