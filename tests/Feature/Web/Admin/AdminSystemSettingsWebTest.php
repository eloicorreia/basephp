<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\DTO\Mail\TenantMailConfigData;
use App\Models\MailConfig;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\TenantApiSetting;
use App\Models\TenantAuditSetting;
use App\Models\TenantIntegrationSetting;
use App\Models\TenantNotificationSetting;
use App\Models\TenantPasswordPolicy;
use App\Models\TenantQueueSetting;
use App\Models\TenantSecuritySetting;
use App\Models\TenantSystemSetting;
use App\Models\TenantWebhookSetting;
use App\Models\User;
use App\Services\Mail\Contracts\TenantMailConnectionTesterInterface;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantMigrationService;
use App\Services\Tenant\TenantSchemaService;
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

    public function test_admin_can_view_all_system_settings_pages(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        foreach ([
            'admin.system-settings.general.edit',
            'admin.system-settings.security.edit',
            'admin.system-settings.password-policy.edit',
            'admin.system-settings.mail.edit',
            'admin.system-settings.api.edit',
            'admin.system-settings.queues.edit',
            'admin.system-settings.audit.edit',
            'admin.system-settings.integrations.edit',
            'admin.system-settings.webhooks.edit',
            'admin.system-settings.notifications.edit',
        ] as $routeName) {
            $this->actingAs($user, 'web')
                ->get(route($routeName, ['tenant' => $tenant->code]))
                ->assertOk();
        }
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

    public function test_password_policy_is_isolated_between_real_tenant_schemas(): void
    {
        $user = $this->adminUser();
        $tenantA = $this->createMigratedSettingsTenant('tenant_settings_a', 'settings-a');
        $tenantB = $this->createMigratedSettingsTenant('tenant_settings_b', 'settings-b');

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.password-policy.update'), [
                'tenant' => $tenantA->code,
                'min_length' => 16,
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
            ->assertRedirect(route('admin.system-settings.password-policy.edit', ['tenant' => $tenantA->code]));

        $this->assertSame(16, $this->tenantValue($tenantA, static fn (): int => (int) TenantPasswordPolicy::query()->firstOrFail()->min_length));
        $this->assertSame(0, $this->tenantValue($tenantB, static fn (): int => TenantPasswordPolicy::query()->count()));
    }

    public function test_system_setting_sections_are_isolated_between_real_tenant_schemas(): void
    {
        $user = $this->adminUser();
        $tenantA = $this->createMigratedSettingsTenant('tenant_settings_c', 'settings-c');
        $tenantB = $this->createMigratedSettingsTenant('tenant_settings_d', 'settings-d');

        foreach ($this->settingIsolationPayloads($tenantA) as $case) {
            $this->actingAs($user, 'web')
                ->put(route($case['route']), $case['payload'])
                ->assertRedirect(route($case['redirect'], ['tenant' => $tenantA->code]));

            $this->assertSame(
                $case['expected'],
                $this->tenantValue($tenantA, $case['actual']),
                $case['route'].' should update tenant A.'
            );

            $this->assertSame(
                0,
                $this->tenantValue($tenantB, $case['count']),
                $case['route'].' should not create records in tenant B.'
            );
        }
    }

    public function test_admin_can_update_mail_settings_with_encrypted_password(): void
    {
        $this->bindSuccessfulMailConnectionTester();
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

    public function test_mail_settings_are_not_saved_when_connection_test_fails(): void
    {
        $this->app->bind(TenantMailConnectionTesterInterface::class, static fn () => new class implements TenantMailConnectionTesterInterface
        {
            public function test(TenantMailConfigData $config): void
            {
                throw new \App\Exceptions\Mail\TenantMailConnectionException;
            }
        });
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
                'timeout_seconds' => 30,
                'verify_peer' => '1',
                'verify_peer_name' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('mail');

        $this->assertSame(0, MailConfig::query()->count());
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

    private function createMigratedSettingsTenant(string $schemaName, string $code): Tenant
    {
        app(TenantSchemaService::class)->createSchema($schemaName);
        app(TenantMigrationService::class)->runTenantMigrations($schemaName, true);

        return $this->createTenant(
            code: $code,
            name: 'Settings '.$code,
            schemaName: $schemaName,
        );
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

    /**
     * @return list<array{
     *     route: string,
     *     redirect: string,
     *     payload: array<string, mixed>,
     *     expected: mixed,
     *     actual: callable(): mixed,
     *     count: callable(): int
     * }>
     */
    private function settingIsolationPayloads(Tenant $tenant): array
    {
        return [
            [
                'route' => 'admin.system-settings.general.update',
                'redirect' => 'admin.system-settings.general.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'timezone' => 'America/Sao_Paulo',
                    'locale' => 'pt_BR',
                    'date_format' => 'd/m/Y',
                    'datetime_format' => 'd/m/Y H:i',
                    'default_items_per_page' => 25,
                    'max_items_per_page' => 100,
                    'support_email' => 'suporte@example.com',
                    'support_phone' => '11999999999',
                    'maintenance_mode' => '1',
                    'maintenance_message' => 'Janela programada',
                ],
                'expected' => 25,
                'actual' => static fn (): int => (int) TenantSystemSetting::query()->firstOrFail()->default_items_per_page,
                'count' => static fn (): int => TenantSystemSetting::query()->count(),
            ],
            [
                'route' => 'admin.system-settings.security.update',
                'redirect' => 'admin.system-settings.security.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'session_lifetime_minutes' => 90,
                    'idle_timeout_minutes' => 30,
                    'force_single_session_per_user' => '1',
                    'logout_on_password_change' => '1',
                    'max_login_attempts' => 4,
                    'lockout_duration_minutes' => 20,
                    'unlock_requires_admin' => '1',
                    'notify_user_on_failed_login' => '1',
                    'notify_admin_on_lockout' => '1',
                    'allowed_ip_ranges' => "10.0.0.0/24\n127.0.0.1",
                ],
                'expected' => 4,
                'actual' => static fn (): int => (int) TenantSecuritySetting::query()->firstOrFail()->max_login_attempts,
                'count' => static fn (): int => TenantSecuritySetting::query()->count(),
            ],
            [
                'route' => 'admin.system-settings.api.update',
                'redirect' => 'admin.system-settings.api.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'api_enabled' => '1',
                    'api_rate_limit_per_minute' => 120,
                    'strict_rate_limit_per_minute' => 40,
                    'login_rate_limit_per_minute' => 8,
                    'api_default_pagination_size' => 20,
                    'api_max_pagination_size' => 200,
                    'api_require_correlation_id' => '1',
                    'api_allowed_origins' => "https://app.example.com\nhttps://admin.example.com",
                ],
                'expected' => 120,
                'actual' => static fn (): int => (int) TenantApiSetting::query()->firstOrFail()->api_rate_limit_per_minute,
                'count' => static fn (): int => TenantApiSetting::query()->count(),
            ],
            [
                'route' => 'admin.system-settings.queues.update',
                'redirect' => 'admin.system-settings.queues.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'default_queue' => 'tenant-default',
                    'email_queue' => 'tenant-email',
                    'max_job_attempts' => 5,
                    'job_retry_delay_seconds' => 120,
                    'failed_job_notify_admin' => '1',
                    'queue_processing_enabled' => '1',
                ],
                'expected' => 'tenant-email',
                'actual' => static fn (): string => (string) TenantQueueSetting::query()->firstOrFail()->email_queue,
                'count' => static fn (): int => TenantQueueSetting::query()->count(),
            ],
            [
                'route' => 'admin.system-settings.audit.update',
                'redirect' => 'admin.system-settings.audit.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'audit_enabled' => '1',
                    'audit_store_before_after' => '1',
                    'audit_payload_enabled' => '1',
                    'audit_sensitive_payload_masking' => '1',
                    'api_request_log_retention_days' => 120,
                    'audit_log_retention_days' => 400,
                    'integration_log_retention_days' => 200,
                    'email_log_retention_days' => 210,
                    'queue_log_retention_days' => 100,
                ],
                'expected' => 400,
                'actual' => static fn (): int => (int) TenantAuditSetting::query()->firstOrFail()->audit_log_retention_days,
                'count' => static fn (): int => TenantAuditSetting::query()->count(),
            ],
            [
                'route' => 'admin.system-settings.integrations.update',
                'redirect' => 'admin.system-settings.integrations.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'integration_enabled' => '1',
                    'default_timeout_seconds' => 45,
                    'retry_attempts' => 4,
                    'retry_backoff_seconds' => 10,
                    'circuit_breaker_enabled' => '1',
                    'circuit_breaker_failure_threshold' => 7,
                ],
                'expected' => 45,
                'actual' => static fn (): int => (int) TenantIntegrationSetting::query()->firstOrFail()->default_timeout_seconds,
                'count' => static fn (): int => TenantIntegrationSetting::query()->count(),
            ],
            [
                'route' => 'admin.system-settings.webhooks.update',
                'redirect' => 'admin.system-settings.webhooks.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'webhook_enabled' => '1',
                    'webhook_url' => 'https://webhook.example.com/events',
                    'webhook_secret' => 'secret-value',
                    'webhook_events' => "user.created\nemail.failed",
                    'webhook_retry_attempts' => 4,
                    'webhook_timeout_seconds' => 20,
                ],
                'expected' => 'https://webhook.example.com/events',
                'actual' => static fn (): string => (string) TenantWebhookSetting::query()->firstOrFail()->webhook_url,
                'count' => static fn (): int => TenantWebhookSetting::query()->count(),
            ],
            [
                'route' => 'admin.system-settings.notifications.update',
                'redirect' => 'admin.system-settings.notifications.edit',
                'payload' => [
                    'tenant' => $tenant->code,
                    'notify_admin_on_failed_jobs' => '1',
                    'notify_admin_on_email_failure' => '1',
                    'notify_admin_on_permission_change' => '1',
                    'notify_admin_on_integration_failure' => '1',
                    'admin_notification_emails' => "admin@example.com\nops@example.com",
                ],
                'expected' => ['admin@example.com', 'ops@example.com'],
                'actual' => static fn (): ?array => TenantNotificationSetting::query()->firstOrFail()->admin_notification_emails,
                'count' => static fn (): int => TenantNotificationSetting::query()->count(),
            ],
        ];
    }

    private function bindSuccessfulMailConnectionTester(): void
    {
        $this->app->bind(TenantMailConnectionTesterInterface::class, static fn () => new class implements TenantMailConnectionTesterInterface
        {
            public function test(TenantMailConfigData $config): void {}
        });
    }
}
