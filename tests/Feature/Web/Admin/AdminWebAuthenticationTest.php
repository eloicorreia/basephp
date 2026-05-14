<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\Models\User;
use App\Services\Admin\Web\AdminWebAuditService;
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
            ->assertSee('Esqueci minha senha');
    }

    public function test_admin_user_can_login_with_web_guard(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'admin-web@example.com',
            'password' => 'secret-password',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin-web@example.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->refresh()->last_login_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AdminWebAuditService::LOGIN_SUCCESS,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_non_admin_user_cannot_login_to_admin_web_module(): void
    {
        $role = $this->createRole(RoleCode::USUARIO->value, 'Usuário');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'user-web@example.com',
            'password' => 'secret-password',
        ]);

        $this->post('/admin/login', [
            'email' => 'user-web@example.com',
            'password' => 'secret-password',
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
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role, overrides: [
            'email' => 'failed-admin-web@example.com',
            'password' => 'secret-password',
        ]);

        $this->post('/admin/login', [
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
}
