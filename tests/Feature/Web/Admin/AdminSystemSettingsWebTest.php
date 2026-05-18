<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\DTO\Mail\TenantMailConfigData;
use App\Enums\RoleCode;
use App\Exceptions\Mail\TenantMailConnectionException;
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
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
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

    public function test_general_settings_page_renders_controlled_fields_and_documented_help(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->get(route('admin.system-settings.general.edit', ['tenant' => $tenant->code]))
            ->assertOk()
            ->assertSee('<select class="form-select" id="timezone" name="timezone" required>', false)
            ->assertSee('<option value="America/Sao_Paulo"', false)
            ->assertSee('<select class="form-select" id="locale" name="locale" required>', false)
            ->assertSee('Português (Brasil) (pt_BR)')
            ->assertSee('dd/mm/aaaa')
            ->assertSee('dd/mm/aaaa hh:mm')
            ->assertSee('data-bs-toggle="tooltip"', false)
            ->assertSee('Aceita HTML para destacar links, listas e instruções formatadas.', false)
            ->assertSee('placeholder="(11) 99999-9999"', false)
            ->assertSee('Informe um e-mail válido.');
    }

    public function test_general_settings_reject_unlisted_timezone_locale_and_formats(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->from(route('admin.system-settings.general.edit', ['tenant' => $tenant->code]))
            ->put(route('admin.system-settings.general.update'), $this->generalSettingsPayload($tenant, [
                'timezone' => 'America/Inventada',
                'locale' => 'xx_FAKE',
                'date_format' => 'Y-m-d',
                'datetime_format' => 'Y-m-d H:i:s',
            ]))
            ->assertUnprocessable()
            ->assertJsonFragment(['field' => 'timezone'])
            ->assertJsonFragment(['field' => 'locale'])
            ->assertJsonFragment(['field' => 'date_format'])
            ->assertJsonFragment(['field' => 'datetime_format']);
    }

    public function test_general_settings_accept_html_maintenance_message(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();
        $message = '<strong>Manutenção programada</strong><br><a href="https://status.example.com">Status</a>';

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.general.update'), $this->generalSettingsPayload($tenant, [
                'maintenance_message' => $message,
            ]))
            ->assertRedirect(route('admin.system-settings.general.edit', ['tenant' => $tenant->code]));

        $this->assertSame($message, TenantSystemSetting::query()->firstOrFail()->maintenance_message);
    }

    public function test_security_settings_page_renders_documented_help_and_constrained_inputs(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->get(route('admin.system-settings.security.edit', ['tenant' => $tenant->code]))
            ->assertOk()
            ->assertSee('data-bs-toggle="tooltip"', false)
            ->assertSee('Tempo total de validade da sessão web do tenant.', false)
            ->assertSee('Lista opcional de IPs ou CIDRs autorizados', false)
            ->assertSee('min="5" max="10080"', false)
            ->assertSee('placeholder="192.168.0.10&#10;10.0.0.0/24"', false);
    }

    public function test_security_settings_reject_invalid_ip_ranges(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.security.update'), [
                'tenant' => $tenant->code,
                'session_lifetime_minutes' => 90,
                'idle_timeout_minutes' => 30,
                'max_login_attempts' => 4,
                'lockout_duration_minutes' => 20,
                'allowed_ip_ranges' => "10.0.0.0/99\nisso-nao-e-ip",
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['field' => 'allowed_ip_ranges']);
    }

    public function test_password_policy_page_renders_documented_help(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->get(route('admin.system-settings.password-policy.edit', ['tenant' => $tenant->code]))
            ->assertOk()
            ->assertSee('data-bs-toggle="tooltip"', false)
            ->assertSee('Quantidade mínima de caracteres exigida', false)
            ->assertSee('Bloqueia senhas previsíveis ou muito comuns', false)
            ->assertSee('Define se esta política está ativa', false);
    }

    public function test_remaining_settings_pages_render_documented_help(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        foreach ([
            ['route' => 'admin.system-settings.mail.edit', 'text' => 'Servidor SMTP usado para enviar e-mails'],
            ['route' => 'admin.system-settings.api.edit', 'text' => 'Lista de origens CORS autorizadas'],
            ['route' => 'admin.system-settings.queues.edit', 'text' => 'Fila usada como padrão para jobs'],
            ['route' => 'admin.system-settings.audit.edit', 'text' => 'Quantidade de dias para manter logs'],
            ['route' => 'admin.system-settings.integrations.edit', 'text' => 'Tempo máximo, em segundos, para chamadas de integrações'],
            ['route' => 'admin.system-settings.webhooks.edit', 'text' => 'Eventos que disparam o webhook'],
            ['route' => 'admin.system-settings.notifications.edit', 'text' => 'Lista de destinatários administrativos'],
        ] as $case) {
            $this->actingAs($user, 'web')
                ->get(route($case['route'], ['tenant' => $tenant->code]))
                ->assertOk()
                ->assertSee('data-bs-toggle="tooltip"', false)
                ->assertSee($case['text'], false);
        }
    }

    public function test_api_settings_reject_invalid_allowed_origins(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.api.update'), [
                'tenant' => $tenant->code,
                'api_rate_limit_per_minute' => 120,
                'strict_rate_limit_per_minute' => 40,
                'login_rate_limit_per_minute' => 8,
                'api_default_pagination_size' => 20,
                'api_max_pagination_size' => 200,
                'api_allowed_origins' => "https://app.example.com\nnot-an-origin",
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['field' => 'api_allowed_origins']);
    }

    public function test_queue_settings_reject_unsafe_queue_names(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.queues.update'), [
                'tenant' => $tenant->code,
                'default_queue' => 'tenant default',
                'email_queue' => 'tenant-email',
                'max_job_attempts' => 5,
                'job_retry_delay_seconds' => 120,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['field' => 'default_queue']);
    }

    public function test_webhook_settings_reject_invalid_event_names(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.webhooks.update'), [
                'tenant' => $tenant->code,
                'webhook_url' => 'https://hooks.example.com/tenant',
                'webhook_events' => "invoice.created\nusuario criado",
                'webhook_retry_attempts' => 3,
                'webhook_timeout_seconds' => 15,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['field' => 'webhook_events']);
    }

    public function test_notification_settings_reject_invalid_admin_emails(): void
    {
        $user = $this->adminUser();
        $tenant = $this->createSettingsTenant();

        $this->actingAs($user, 'web')
            ->put(route('admin.system-settings.notifications.update'), [
                'tenant' => $tenant->code,
                'admin_notification_emails' => "admin@example.com\nemail-invalido",
            ])
            ->assertUnprocessable()
            ->assertJsonFragment(['field' => 'admin_notification_emails']);
    }

    public function test_runtime_settings_apply_default_and_max_pagination_to_tenant_api(): void
    {
        $context = $this->createRuntimeApiContext();
        TenantSystemSetting::query()->create($this->tenantSystemSettingAttributes($context['tenant'], [
            'default_items_per_page' => 3,
            'max_items_per_page' => 7,
        ]));

        $this->getJson('/api/v1/admin/roles', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('meta.per_page', 3);

        $this->getJson('/api/v1/admin/roles?per_page=500', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('meta.per_page', 7);
    }

    public function test_runtime_settings_apply_datetime_format_to_tenant_api_resources(): void
    {
        $context = $this->createRuntimeApiContext();
        TenantSystemSetting::query()->create($this->tenantSystemSettingAttributes($context['tenant'], [
            'datetime_format' => 'd/m/Y H:i',
        ]));

        $role = $this->createRole('runtime-date-role', 'Runtime Date Role');

        $response = $this->getJson('/api/v1/admin/roles?per_page=50', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertOk();

        $listedRole = collect($response->json('data'))->firstWhere('code', $role->code);

        $this->assertIsArray($listedRole);
        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', (string) $listedRole['created_at']);
    }

    public function test_runtime_settings_block_tenant_api_when_maintenance_mode_is_enabled(): void
    {
        $context = $this->createRuntimeApiContext();
        TenantSystemSetting::query()->create($this->tenantSystemSettingAttributes($context['tenant'], [
            'maintenance_mode' => '1',
            'maintenance_message' => '<strong>Manutenção programada</strong>',
        ]));

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sistema em manutenção.')
            ->assertJsonFragment(['type' => 'MAINTENANCE_MODE']);
    }

    public function test_security_settings_block_tenant_api_for_disallowed_ip(): void
    {
        $context = $this->createRuntimeApiContext();
        TenantSecuritySetting::query()->create($this->tenantSecuritySettingAttributes([
            'allowed_ip_ranges' => ['10.0.0.0/24'],
        ]));

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson('/api/v1/auth/me', [
                'X-Tenant-Id' => $context['tenant']->code,
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'IP não autorizado para este tenant.');
    }

    public function test_security_settings_block_locked_tenant_user(): void
    {
        $context = $this->createRuntimeApiContext();
        TenantSecuritySetting::query()->create($this->tenantSecuritySettingAttributes());
        $context['user']->forceFill(['locked_by_admin' => true])->save();

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(423)
            ->assertJsonPath('message', 'Usuário bloqueado. Solicite desbloqueio ao administrador.');
    }

    public function test_security_settings_block_idle_tenant_api_session(): void
    {
        $context = $this->createRuntimeApiContext();
        TenantSecuritySetting::query()->create($this->tenantSecuritySettingAttributes([
            'idle_timeout_minutes' => 10,
        ]));
        Cache::put(
            sprintf('tenant_security:last_activity:tenant:%d:user:%d:token:transient', $context['tenant']->id, $context['user']->id),
            now()->subMinutes(11)->timestamp,
            now()->addMinutes(5)
        );

        $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Sessão expirada por inatividade.');
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
                throw new TenantMailConnectionException;
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

    /**
     * @return array{tenant: Tenant, user: User}
     */
    private function createRuntimeApiContext(): array
    {
        $tenant = $this->createSettingsTenant();
        $adminRole = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $tenantRole = $this->createRole('runtime-tenant-admin', 'Runtime Tenant Admin');
        $user = $this->createUser(role: $adminRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function generalSettingsPayload(Tenant $tenant, array $overrides = []): array
    {
        return array_merge([
            'tenant' => $tenant->code,
            'timezone' => 'America/Sao_Paulo',
            'locale' => 'pt_BR',
            'date_format' => 'd/m/Y',
            'datetime_format' => 'd/m/Y H:i',
            'default_items_per_page' => 25,
            'max_items_per_page' => 100,
            'support_email' => 'suporte@example.com',
            'support_phone' => '(11) 99999-9999',
            'maintenance_mode' => '1',
            'maintenance_message' => 'Janela programada',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function tenantSystemSettingAttributes(Tenant $tenant, array $overrides = []): array
    {
        $payload = $this->generalSettingsPayload($tenant, array_merge([
            'maintenance_mode' => '0',
        ], $overrides));
        unset($payload['tenant']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function tenantSecuritySettingAttributes(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
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
