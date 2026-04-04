<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\DTOs\Mail\EmailAddressData;
use App\DTOs\Mail\SendEmailData;
use App\DTOs\Mail\TenantMailConfigData;
use App\Jobs\Mail\SendTenantEmailJob;
use App\Models\EmailDispatchLog;
use App\Models\Tenant;
use App\Services\Logging\LogPersistenceService;
use App\Services\Mail\Contracts\RuntimeMailSenderInterface;
use App\Services\Mail\EmailDispatchLogService;
use App\Services\Mail\TenantMailConfigResolverService;
use App\Services\Tenant\TenantExecutionManager;
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

        $resolver = Mockery::mock(TenantMailConfigResolverService::class);
        $resolver->shouldReceive('resolveDefault')
            ->once()
            ->andReturn(new TenantMailConfigData(
                id: 1,
                name: 'SMTP padrão',
                driver: 'smtp',
                host: 'smtp.example.com',
                port: 587,
                encryption: 'tls',
                username: 'user',
                password: 'secret',
                fromAddress: 'no-reply@example.com',
                fromName: 'Sistema',
                replyToAddress: null,
                replyToName: null,
                timeoutSeconds: 30,
                verifyPeer: true,
                verifyPeerName: true,
                allowSelfSigned: false,
            ));

        $sender = Mockery::mock(RuntimeMailSenderInterface::class);
        $sender->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('SMTP indisponível.'));

        $logPersistence = Mockery::mock(LogPersistenceService::class);
        $logPersistence->shouldReceive('logSystemError')->once();

        $this->app->instance(TenantMailConfigResolverService::class, $resolver);
        $this->app->instance(RuntimeMailSenderInterface::class, $sender);
        $this->app->instance(LogPersistenceService::class, $logPersistence);

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
                app(TenantMailConfigResolverService::class),
                app(RuntimeMailSenderInterface::class),
                app(EmailDispatchLogService::class),
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