<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\DTO\Mail\EmailAddressData;
use App\DTO\Mail\SendEmailData;
use App\DTO\Mail\TenantMailConfigData;
use App\Exceptions\Mail\TenantMailConnectionException;
use App\Models\MailConfig;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Services\Mail\Contracts\RuntimeMailSenderInterface;
use App\Services\Mail\Contracts\TenantMailConnectionTesterInterface;
use App\Services\Mail\TenantMailConfigResolverService;
use App\Support\Auth\AuthenticatedUserId;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class TenantMailSettingService
{
    public function __construct(
        private Encrypter $encrypter,
        private LogPersistenceService $logPersistenceService,
        private TenantMailConfigResolverService $mailConfigResolverService,
        private RuntimeMailSenderInterface $runtimeMailSender,
        private TenantMailConnectionTesterInterface $connectionTester,
    ) {}

    public function defaultConfig(): ?MailConfig
    {
        return MailConfig::query()
            ->where('is_default', true)
            ->orderByDesc('is_active')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDefault(array $data): MailConfig
    {
        return DB::transaction(function () use ($data): MailConfig {
            $authenticatedUser = auth()->user();
            $config = $this->defaultConfig() ?? new MailConfig(['name' => 'SMTP padrão']);
            $before = $config->exists ? $this->auditSnapshot($config) : null;
            $password = (string) ($data['password'] ?? '');
            $runtimePassword = $password !== '' ? $password : $this->existingPassword($config);

            $this->testConnectionFromPayload($data, $runtimePassword);

            MailConfig::query()->where('is_default', true)->update(['is_default' => false]);

            $config->fill([
                'name' => $data['name'],
                'driver' => 'smtp',
                'host' => $data['host'],
                'port' => $data['port'],
                'encryption' => $data['encryption'] ?? null,
                'username' => $data['username'] ?? null,
                'from_address' => $data['from_address'],
                'from_name' => $data['from_name'],
                'reply_to_address' => $data['reply_to_address'] ?? null,
                'reply_to_name' => $data['reply_to_name'] ?? null,
                'timeout_seconds' => $data['timeout_seconds'],
                'verify_peer' => (bool) ($data['verify_peer'] ?? true),
                'verify_peer_name' => (bool) ($data['verify_peer_name'] ?? true),
                'allow_self_signed' => (bool) ($data['allow_self_signed'] ?? false),
                'is_active' => (bool) ($data['is_active'] ?? false),
                'is_default' => true,
                'created_by' => $config->exists ? $config->created_by : AuthenticatedUserId::resolve(),
                'updated_by' => AuthenticatedUserId::resolve(),
            ]);

            if ($password !== '') {
                $config->password_encrypted = $this->encrypter->encryptString($password);
            }

            $config->save();

            $this->logPersistenceService->logAudit(
                action: 'tenant_settings.mail.updated',
                auditableType: MailConfig::class,
                auditableId: (int) $config->id,
                beforeData: $before,
                afterData: $this->auditSnapshot($config->refresh()),
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );

            return $config;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function testConnectionFromPayload(array $data, ?string $password = null): void
    {
        $authenticatedUser = auth()->user();
        $runtimeConfig = new TenantMailConfigData(
            id: 0,
            name: (string) $data['name'],
            driver: 'smtp',
            host: (string) $data['host'],
            port: (int) $data['port'],
            encryption: $data['encryption'] ?? null,
            username: $data['username'] ?? null,
            password: $password,
            fromAddress: (string) $data['from_address'],
            fromName: (string) $data['from_name'],
            replyToAddress: $data['reply_to_address'] ?? null,
            replyToName: $data['reply_to_name'] ?? null,
            timeoutSeconds: (int) $data['timeout_seconds'],
            verifyPeer: (bool) ($data['verify_peer'] ?? true),
            verifyPeerName: (bool) ($data['verify_peer_name'] ?? true),
            allowSelfSigned: (bool) ($data['allow_self_signed'] ?? false),
        );

        try {
            $this->connectionTester->test($runtimeConfig);
            $this->logPersistenceService->logAudit(
                action: 'tenant_settings.mail.connection_test_succeeded',
                auditableType: MailConfig::class,
                auditableId: null,
                beforeData: null,
                afterData: $this->connectionAuditPayload($data),
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );
        } catch (TenantMailConnectionException $exception) {
            $this->logPersistenceService->logAudit(
                action: 'tenant_settings.mail.connection_test_failed',
                auditableType: MailConfig::class,
                auditableId: null,
                beforeData: null,
                afterData: $this->connectionAuditPayload($data),
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );

            throw $exception;
        }
    }

    public function sendTest(string $to): void
    {
        $authenticatedUser = auth()->user();
        $config = $this->mailConfigResolverService->resolveDefault();

        try {
            $this->runtimeMailSender->send($config, new SendEmailData(
                trigger: 'tenant_settings.mail.test_requested',
                subject: 'Teste de e-mail do tenant',
                htmlBody: '<p>Configuração de e-mail validada com sucesso.</p>',
                textBody: 'Configuração de e-mail validada com sucesso.',
                to: [new EmailAddressData($to)],
            ));

            $this->logPersistenceService->logAudit(
                action: 'tenant_settings.mail.test_succeeded',
                auditableType: MailConfig::class,
                auditableId: $config->id,
                beforeData: null,
                afterData: ['to' => $to, 'mail_config_id' => $config->id],
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );
        } catch (Throwable $throwable) {
            $this->logPersistenceService->logAudit(
                action: 'tenant_settings.mail.test_failed',
                auditableType: MailConfig::class,
                auditableId: $config->id,
                beforeData: null,
                afterData: ['to' => $to, 'error_class' => $throwable::class],
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );

            throw $throwable;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function auditSnapshot(MailConfig $config): array
    {
        return [
            'name' => $config->name,
            'driver' => $config->driver,
            'host' => $config->host,
            'port' => $config->port,
            'encryption' => $config->encryption,
            'username' => $config->username !== null ? '***' : null,
            'password_encrypted' => $config->password_encrypted !== null ? '***' : null,
            'from_address' => $config->from_address,
            'from_name' => $config->from_name,
            'reply_to_address' => $config->reply_to_address,
            'timeout_seconds' => $config->timeout_seconds,
            'is_active' => $config->is_active,
            'is_default' => $config->is_default,
        ];
    }

    private function existingPassword(MailConfig $config): ?string
    {
        if (! $config->exists || $config->password_encrypted === null) {
            return null;
        }

        return $this->encrypter->decryptString((string) $config->password_encrypted);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function connectionAuditPayload(array $data): array
    {
        return [
            'host' => $data['host'] ?? null,
            'port' => $data['port'] ?? null,
            'encryption' => $data['encryption'] ?? null,
            'username' => ! empty($data['username']) ? '***' : null,
        ];
    }
}
