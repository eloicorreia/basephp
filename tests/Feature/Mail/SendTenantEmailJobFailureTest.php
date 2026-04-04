<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\DTO\Mail\EmailAddressData;
use App\DTO\Mail\SendEmailData;
use App\Jobs\Mail\SendTenantEmailJob;
use App\Models\EmailDispatchLog;
use App\Models\MailConfig;
use App\Models\Tenant;
use App\Services\Logging\IntegrationLogger;
use App\Services\Logging\LogPersistenceService;
use App\Services\Mail\Contracts\RuntimeMailSenderInterface;
use App\Services\Mail\EmailDispatchLogService;
use App\Services\Mail\TenantMailConfigResolverService;
use App\Services\Tenant\TenantExecutionManager;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class SendTenantEmailJobFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_log_as_failed_when_sender_throws(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-a',
            'name' => 'Tenant A',
            'schema_name' => 'tenant_a',
            'status' => 'active',
        ]);

        $encrypter = app(Encrypter::class);

        MailConfig::query()->create([
            'name' => 'SMTP padrão',
            'driver' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'user',
            'password_encrypted' => $encrypter->encryptString('secret'),
            'from_address' => 'no-reply@example.com',
            'from_name' => 'Sistema',
            'reply_to_address' => null,
            'reply_to_name' => null,
            'timeout_seconds' => 30,
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
            'is_active' => true,
            'is_default' => true,
            'metadata' => null,
        ]);

        $log = EmailDispatchLog::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'trigger' => 'invoice.created',
            'status' => 'queued',
            'attempt_count' => 0,
            'to_recipients' => [
                ['email' => 'teste@example.com', 'name' => 'Teste'],
            ],
            'cc_recipients' => [],
            'bcc_recipients' => [],
            'subject' => 'Teste',
        ]);

        $sender = Mockery::mock(RuntimeMailSenderInterface::class);
        $sender->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('SMTP indisponível.'));

        $integrationLogger = Mockery::mock(IntegrationLogger::class);

        $logPersistenceService = Mockery::mock(LogPersistenceService::class);
        $logPersistenceService->shouldReceive('logSystemError')
            ->once();

        $tenantContext = app(TenantContext::class);

        $emailDispatchLogService = new EmailDispatchLogService(
            tenantContext: $tenantContext,
            logPersistenceService: $logPersistenceService,
            integrationLogger: $integrationLogger,
        );

        $resolver = app(TenantMailConfigResolverService::class);

        $job = new SendTenantEmailJob(
            tenantId: (int) $tenant->id,
            emailDispatchLogId: (int) $log->id,
            emailData: new SendEmailData(
                trigger: 'invoice.created',
                subject: 'Teste',
                htmlBody: '<p>Olá</p>',
                textBody: 'Olá',
                to: [
                    new EmailAddressData('teste@example.com', 'Teste'),
                ],
            ),
        );

        try {
            $job->handle(
                app(TenantExecutionManager::class),
                $resolver,
                $sender,
                $emailDispatchLogService,
            );

            $this->fail('Era esperado que o job lançasse exceção.');
        } catch (RuntimeException $exception) {
            $this->assertSame('SMTP indisponível.', $exception->getMessage());
        }

        $this->assertDatabaseHas('email_dispatch_logs', [
            'id' => $log->id,
            'status' => 'failed',
            'error_message' => 'SMTP indisponível.',
        ]);
    }
}