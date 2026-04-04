<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Jobs\SendEmailJob;
use App\Models\EmailDispatch;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class EmailDispatchService
{
    public function __construct(
        private LogPersistenceService $logPersistenceService,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatch(array $payload, ?int $actorId, ?string $actorRole): EmailDispatch
    {
        return DB::transaction(function () use ($payload, $actorId, $actorRole): EmailDispatch {
            $emailDispatch = EmailDispatch::query()->create([
                'requested_by_user_id' => $actorId,
                'requested_by_role' => $actorRole,
                'queue' => $payload['queue'] ?? 'notifications',
                'to_recipients' => $payload['to'],
                'cc_recipients' => $payload['cc'] ?? null,
                'bcc_recipients' => $payload['bcc'] ?? null,
                'subject' => $payload['subject'],
                'body' => $payload['body'],
                'is_html' => (bool) ($payload['is_html'] ?? false),
                'status' => 'queued',
                'external_reference' => $payload['external_reference'] ?? null,
            ]);

            SendEmailJob::dispatch($emailDispatch->id)
                ->onQueue($emailDispatch->queue);

            $this->logPersistenceService->logAudit(
                action: 'email.dispatch.requested',
                auditableType: EmailDispatch::class,
                auditableId: $emailDispatch->id,
                beforeData: null,
                afterData: [
                    'queue' => $emailDispatch->queue,
                    'status' => $emailDispatch->status,
                    'subject' => $emailDispatch->subject,
                ],
                userId: $actorId,
                userRole: $actorRole,
            );

            $this->logPersistenceService->logSystemInfo(
                message: 'Solicitação de envio de e-mail registrada com sucesso.',
                category: 'mail',
                operation: 'dispatch',
                userId: $actorId,
                context: [
                    'email_dispatch_id' => $emailDispatch->id,
                    'queue' => $emailDispatch->queue,
                    'external_reference' => $emailDispatch->external_reference,
                ],
                httpStatus: 202,
                processingStatus: 'queued',
            );

            return $emailDispatch;
        });
    }

    public function list(?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return EmailDispatch::query()
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function retry(EmailDispatch $emailDispatch, ?int $actorId, ?string $actorRole): EmailDispatch
    {
        $emailDispatch->update([
            'status' => 'queued',
            'error_message' => null,
            'sent_at' => null,
        ]);

        SendEmailJob::dispatch($emailDispatch->id)
            ->onQueue($emailDispatch->queue);

        $this->logPersistenceService->logAudit(
            action: 'email.dispatch.retried',
            auditableType: EmailDispatch::class,
            auditableId: $emailDispatch->id,
            beforeData: ['status' => 'failed'],
            afterData: ['status' => 'queued'],
            userId: $actorId,
            userRole: $actorRole,
        );

        return $emailDispatch->fresh();
    }
}