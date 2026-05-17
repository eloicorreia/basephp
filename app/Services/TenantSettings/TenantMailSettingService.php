<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\DTO\Mail\EmailAddressData;
use App\DTO\Mail\SendEmailData;
use App\Models\MailConfig;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Services\Mail\Contracts\RuntimeMailSenderInterface;
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
                'verify_peer' => (bool) ($data['verify_peer'] ?? false),
                'verify_peer_name' => (bool) ($data['verify_peer_name'] ?? false),
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
                afterData: ['to' => $to, 'error' => $throwable->getMessage()],
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
}
