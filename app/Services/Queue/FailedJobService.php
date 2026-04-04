<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Models\FailedJob;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;

final readonly class FailedJobService
{
    public function __construct(
        private LogPersistenceService $logPersistenceService,
    ) {
    }

    public function list(?string $queue, int $perPage = 15): LengthAwarePaginator
    {
        return FailedJob::query()
            ->when($queue !== null, fn ($query) => $query->where('queue', $queue))
            ->orderByDesc('failed_at')
            ->paginate($perPage);
    }

    public function retry(FailedJob $failedJob, ?int $userId): void
    {
        Artisan::call('queue:retry', [
            'id' => [$failedJob->uuid ?? (string) $failedJob->id],
        ]);

        $this->logPersistenceService->logSystemInfo(
            message: 'Retry de failed job solicitado com sucesso.',
            category: 'queue',
            operation: 'failed-job-retry',
            userId: $userId,
            context: [
                'failed_job_id' => $failedJob->id,
                'failed_job_uuid' => $failedJob->uuid,
                'queue' => $failedJob->queue,
            ],
            httpStatus: 202,
            processingStatus: 'queued',
        );
    }

    public function destroy(FailedJob $failedJob, ?int $userId): void
    {
        $failedJobId = $failedJob->id;
        $queue = $failedJob->queue;

        $failedJob->delete();

        $this->logPersistenceService->logSystemWarning(
            message: 'Failed job removido da fila de falhas.',
            category: 'queue',
            operation: 'failed-job-delete',
            userId: $userId,
            context: [
                'failed_job_id' => $failedJobId,
                'queue' => $queue,
            ],
            httpStatus: 200,
            processingStatus: 'success',
        );
    }
}