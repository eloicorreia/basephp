<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\EmailDispatch;
use App\Models\QueueExecutionLog;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $emailDispatchId,
    ) {
    }

    public function handle(LogPersistenceService $logPersistenceService): void
    {
        $dispatch = EmailDispatch::query()->findOrFail($this->emailDispatchId);

        $dispatch->update([
            'status' => 'processing',
            'attempts' => $dispatch->attempts + 1,
            'updated_at' => now(),
        ]);

        QueueExecutionLog::query()->create([
            'request_id' => null,
            'trace_id' => null,
            'job_id' => $this->job?->getJobId(),
            'job_uuid' => method_exists($this->job, 'uuid') ? $this->job->uuid() : null,
            'queue' => $this->job?->getQueue() ?? $dispatch->queue,
            'job_class' => self::class,
            'event' => 'processing',
            'attempt' => $this->attempts(),
            'status' => 'processing',
            'message' => 'Processamento de envio de e-mail iniciado.',
            'context' => [
                'email_dispatch_id' => $dispatch->id,
            ],
            'occurred_at' => now(),
        ]);

        try {
            Mail::raw($dispatch->body, function ($message) use ($dispatch): void {
                $message->to($dispatch->to_recipients)
                    ->subject($dispatch->subject);

                if (is_array($dispatch->cc_recipients) && $dispatch->cc_recipients !== []) {
                    $message->cc($dispatch->cc_recipients);
                }

                if (is_array($dispatch->bcc_recipients) && $dispatch->bcc_recipients !== []) {
                    $message->bcc($dispatch->bcc_recipients);
                }
            });

            $dispatch->update([
                'status' => 'sent',
                'error_message' => null,
                'sent_at' => now(),
                'updated_at' => now(),
            ]);

            QueueExecutionLog::query()->create([
                'request_id' => null,
                'trace_id' => null,
                'job_id' => $this->job?->getJobId(),
                'job_uuid' => method_exists($this->job, 'uuid') ? $this->job->uuid() : null,
                'queue' => $this->job?->getQueue() ?? $dispatch->queue,
                'job_class' => self::class,
                'event' => 'completed',
                'attempt' => $this->attempts(),
                'status' => 'success',
                'message' => 'Envio de e-mail concluído com sucesso.',
                'context' => [
                    'email_dispatch_id' => $dispatch->id,
                ],
                'occurred_at' => now(),
            ]);

            $logPersistenceService->logSystemInfo(
                message: 'Envio de e-mail concluído com sucesso.',
                category: 'mail',
                operation: 'send-email-job',
                userId: $dispatch->requested_by_user_id,
                context: [
                    'email_dispatch_id' => $dispatch->id,
                    'queue' => $dispatch->queue,
                ],
                httpStatus: null,
                processingStatus: 'success',
            );
        } catch (Throwable $throwable) {
            $dispatch->update([
                'status' => 'failed',
                'error_message' => $throwable->getMessage(),
                'updated_at' => now(),
            ]);

            QueueExecutionLog::query()->create([
                'request_id' => null,
                'trace_id' => null,
                'job_id' => $this->job?->getJobId(),
                'job_uuid' => method_exists($this->job, 'uuid') ? $this->job->uuid() : null,
                'queue' => $this->job?->getQueue() ?? $dispatch->queue,
                'job_class' => self::class,
                'event' => 'failed',
                'attempt' => $this->attempts(),
                'status' => 'error',
                'message' => 'Falha no envio do e-mail.',
                'context' => [
                    'email_dispatch_id' => $dispatch->id,
                    'exception' => $throwable::class,
                ],
                'occurred_at' => now(),
            ]);

            $logPersistenceService->logSystemError(
                throwable: $throwable,
                category: 'mail',
                operation: 'send-email-job',
                userId: $dispatch->requested_by_user_id,
            );

            throw $throwable;
        }
    }
}