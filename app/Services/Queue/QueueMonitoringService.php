<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Models\Job;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class QueueMonitoringService
{
    public function listJobs(?string $queue, int $perPage = 15): LengthAwarePaginator
    {
        return Job::query()
            ->when($queue !== null, fn ($query) => $query->where('queue', $queue))
            ->orderBy('available_at')
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(?string $queue): array
    {
        $jobsQuery = DB::table('jobs')
            ->when($queue !== null, fn ($query) => $query->where('queue', $queue));

        $failedJobsQuery = DB::table('failed_jobs')
            ->when($queue !== null, fn ($query) => $query->where('queue', $queue));

        $pending = (clone $jobsQuery)
            ->whereNull('reserved_at')
            ->count();

        $running = (clone $jobsQuery)
            ->whereNotNull('reserved_at')
            ->count();

        $failed = (clone $failedJobsQuery)->count();

        return [
            'queue' => $queue,
            'pending_jobs' => $pending,
            'running_jobs' => $running,
            'failed_jobs' => $failed,
            'generated_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Job $job): array
    {
        return [
            'id' => $job->id,
            'queue' => $job->queue,
            'attempts' => $job->attempts,
            'reserved_at' => $job->reserved_at,
            'available_at' => $job->available_at,
            'created_at' => $job->created_at,
            'payload_preview' => $this->extractPayloadPreview($job->payload),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function extractPayloadPreview(?string $payload): ?array
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            return null;
        }

        return [
            'displayName' => $decoded['displayName'] ?? null,
            'job' => $decoded['job'] ?? null,
            'maxTries' => $decoded['maxTries'] ?? null,
            'timeout' => $decoded['timeout'] ?? null,
        ];
    }
}