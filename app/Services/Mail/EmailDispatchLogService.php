<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\DTOs\Mail\SendEmailData;
use App\DTOs\Mail\TenantMailConfigData;
use App\Models\EmailDispatchLog;
use App\Services\Logging\IntegrationLogger;
use App\Services\Logging\LogPersistenceService;
use App\Support\TenantContext;
use Throwable;

final readonly class EmailDispatchLogService
{
    public function __construct(
        private TenantContext $tenantContext,
        private LogPersistenceService $logPersistenceService,
        private IntegrationLogger $integrationLogger,
    ) {
    }

    public function createQueuedLog(SendEmailData $email, ?int $userId = null): EmailDispatchLog
    {
        $tenant = $this->tenantContext->require();

        $log = EmailDispatchLog::query()->create([
            'request_id' => request()?->attributes->get('request_id'),
            'trace_id' => request()?->attributes->get('trace_id'),
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->code,
            'user_id' => $userId,
            'trigger' => $email->trigger,
            'status' => 'queued',
            'attempt_count' => 0,
            'to_recipients' => array_map(
                static fn ($item): array => $item->toArray(),
                $email->to
            ),
            'cc_recipients' => array_map(
                static fn ($item): array => $item->toArray(),
                $email->cc
            ),
            'bcc_recipients' => array_map(
                static fn ($item): array => $item->toArray(),
                $email->bcc
            ),
            'subject' => $email->subject,
            'html_body' => $email->htmlBody,
            'text_body' => $email->textBody,
            'idempotency_key' => $email->idempotencyKey,
            'context' => $email->context,
            'queued_at' => now(),
        ]);

        $this->integrationLogger->logRequest(
            service: 'mail',
            operation: $email->trigger,
            destination: 'smtp',
            payload: [
                'email_dispatch_log_id' => $log->id,
                'subject' => $email->subject,
            ],
        );

        return $log;
    }

    public function markSending(EmailDispatchLog $log, TenantMailConfigData $config, string $queueName, string $queueConnection, ?string $jobUuid): void
    {
        $log->update([
            'status' => 'sending',
            'attempt_count' => $log->attempt_count + 1,
            'mail_config_id' => $config->id,
            'mail_config_name' => $config->name,
            'driver' => $config->driver,
            'host' => $config->host,
            'port' => $config->port,
            'encryption' => $config->encryption,
            'from_address' => $config->fromAddress,
            'from_name' => $config->fromName,
            'queue_name' => $queueName,
            'queue_connection' => $queueConnection,
            'job_uuid' => $jobUuid,
            'sending_started_at' => now(),
        ]);
    }

    public function markSent(EmailDispatchLog $log, ?string $providerMessageId): void
    {
        $log->update([
            'status' => 'sent',
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
        ]);
    }

    public function markFailed(EmailDispatchLog $log, Throwable $throwable): void
    {
        $log->update([
            'status' => 'failed',
            'error_class' => $throwable::class,
            'error_message' => $throwable->getMessage(),
            'stack_trace_summary' => mb_substr(
                $throwable->getFile() . ':' . $throwable->getLine(),
                0,
                1000
            ),
            'failed_at' => now(),
        ]);

        $this->logPersistenceService->logSystemError(
            throwable: $throwable,
            category: 'email',
            operation: $log->trigger,
            userId: $log->user_id,
        );
    }
}