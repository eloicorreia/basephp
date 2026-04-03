<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Models\QueueJobLog;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Str;
use Throwable;

final readonly class QueueExecutionLogService
{
    public function __construct(
        private QueuePayloadSanitizer $payloadSanitizer,
    ) {
    }

    public function logQueued(JobQueued $event): void
    {
        $payload = is_array($event->payload ?? null) ? $event->payload : [];
        $context = $this->extractTechnicalContextFromPayload($payload);

        QueueJobLog::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'category' => 'queue',
            'event_type' => 'dispatched',
            'operation' => 'queue.job.queued',
            'status' => 'queued',
            'job_uuid' => $this->extractJobUuid($payload),
            'batch_id' => $this->extractBatchId($payload),
            'job_class' => $this->extractQueuedJobClass($event, $payload),
            'queue_connection' => (string) $event->connectionName,
            'queue_name' => (string) ($event->queue ?? 'default'),
            'attempt' => 0,
            'max_tries' => $this->extractMaxTries($payload),
            'tenant_id' => $this->extractNullableInt($context, 'tenant_id'),
            'tenant_code' => $this->extractNullableString($context, 'tenant_code'),
            'user_id' => $this->extractNullableInt($context, 'user_id'),
            'oauth_client_id' => $this->extractNullableInt($context, 'oauth_client_id'),
            'request_id' => $this->extractNullableString($context, 'request_id'),
            'trace_id' => $this->extractNullableString($context, 'trace_id'),
            'message' => 'Job queued successfully.',
            'input_payload' => $this->payloadSanitizer->sanitize($payload),
            'context' => $context,
            'processed_at' => now(),
        ]);
    }

    public function logProcessing(JobProcessing $event): void
    {
        $payload = $event->job->payload();
        $context = $this->extractTechnicalContextFromPayload($payload);

        QueueJobLog::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'category' => 'queue',
            'event_type' => 'started',
            'operation' => 'queue.job.processing',
            'status' => 'processing',
            'job_uuid' => $this->extractJobUuid($payload),
            'batch_id' => $this->extractBatchId($payload),
            'job_class' => $this->extractJobClass($payload),
            'queue_connection' => (string) $event->connectionName,
            'queue_name' => (string) $event->job->getQueue(),
            'attempt' => $event->job->attempts(),
            'max_tries' => $event->job->maxTries(),
            'tenant_id' => $this->extractNullableInt($context, 'tenant_id'),
            'tenant_code' => $this->extractNullableString($context, 'tenant_code'),
            'user_id' => $this->extractNullableInt($context, 'user_id'),
            'oauth_client_id' => $this->extractNullableInt($context, 'oauth_client_id'),
            'request_id' => $this->extractNullableString($context, 'request_id'),
            'trace_id' => $this->extractNullableString($context, 'trace_id'),
            'message' => 'Job processing started.',
            'input_payload' => $this->payloadSanitizer->sanitize($payload),
            'context' => $context,
            'processed_at' => now(),
        ]);
    }

    public function logProcessed(JobProcessed $event): void
    {
        $payload = $event->job->payload();
        $context = $this->extractTechnicalContextFromPayload($payload);

        QueueJobLog::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'category' => 'queue',
            'event_type' => 'succeeded',
            'operation' => 'queue.job.processed',
            'status' => 'success',
            'job_uuid' => $this->extractJobUuid($payload),
            'batch_id' => $this->extractBatchId($payload),
            'job_class' => $this->extractJobClass($payload),
            'queue_connection' => (string) $event->connectionName,
            'queue_name' => (string) $event->job->getQueue(),
            'attempt' => $event->job->attempts(),
            'max_tries' => $event->job->maxTries(),
            'tenant_id' => $this->extractNullableInt($context, 'tenant_id'),
            'tenant_code' => $this->extractNullableString($context, 'tenant_code'),
            'user_id' => $this->extractNullableInt($context, 'user_id'),
            'oauth_client_id' => $this->extractNullableInt($context, 'oauth_client_id'),
            'request_id' => $this->extractNullableString($context, 'request_id'),
            'trace_id' => $this->extractNullableString($context, 'trace_id'),
            'message' => 'Job processed successfully.',
            'input_payload' => $this->payloadSanitizer->sanitize($payload),
            'context' => $context,
            'processed_at' => now(),
        ]);
    }

    public function logFailed(JobFailed $event): void
    {
        $payload = $event->job->payload();
        $context = $this->extractTechnicalContextFromPayload($payload);

        QueueJobLog::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'category' => 'queue',
            'event_type' => 'failed',
            'operation' => 'queue.job.failed',
            'status' => 'failed',
            'job_uuid' => $this->extractJobUuid($payload),
            'batch_id' => $this->extractBatchId($payload),
            'job_class' => $this->extractJobClass($payload),
            'queue_connection' => (string) $event->connectionName,
            'queue_name' => (string) $event->job->getQueue(),
            'attempt' => $event->job->attempts(),
            'max_tries' => $event->job->maxTries(),
            'tenant_id' => $this->extractNullableInt($context, 'tenant_id'),
            'tenant_code' => $this->extractNullableString($context, 'tenant_code'),
            'user_id' => $this->extractNullableInt($context, 'user_id'),
            'oauth_client_id' => $this->extractNullableInt($context, 'oauth_client_id'),
            'request_id' => $this->extractNullableString($context, 'request_id'),
            'trace_id' => $this->extractNullableString($context, 'trace_id'),
            'message' => 'Job failed.',
            'exception_class' => $event->exception::class,
            'exception_message' => $this->truncateExceptionMessage($event->exception),
            'input_payload' => $this->payloadSanitizer->sanitize($payload),
            'context' => $context,
            'processed_at' => now(),
        ]);
    }

    public function logException(JobExceptionOccurred $event): void
    {
        $payload = $event->job->payload();
        $context = $this->extractTechnicalContextFromPayload($payload);

        QueueJobLog::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'category' => 'queue',
            'event_type' => 'released',
            'operation' => 'queue.job.exception_occurred',
            'status' => 'released',
            'job_uuid' => $this->extractJobUuid($payload),
            'batch_id' => $this->extractBatchId($payload),
            'job_class' => $this->extractJobClass($payload),
            'queue_connection' => (string) $event->connectionName,
            'queue_name' => (string) $event->job->getQueue(),
            'attempt' => $event->job->attempts(),
            'max_tries' => $event->job->maxTries(),
            'tenant_id' => $this->extractNullableInt($context, 'tenant_id'),
            'tenant_code' => $this->extractNullableString($context, 'tenant_code'),
            'user_id' => $this->extractNullableInt($context, 'user_id'),
            'oauth_client_id' => $this->extractNullableInt($context, 'oauth_client_id'),
            'request_id' => $this->extractNullableString($context, 'request_id'),
            'trace_id' => $this->extractNullableString($context, 'trace_id'),
            'message' => 'Job exception occurred and may be retried.',
            'exception_class' => $event->exception::class,
            'exception_message' => $this->truncateExceptionMessage($event->exception),
            'input_payload' => $this->payloadSanitizer->sanitize($payload),
            'context' => $context,
            'processed_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function extractTechnicalContextFromPayload(array $payload): array
    {
        $data = $payload['data'] ?? [];
        $command = $data['command'] ?? null;

        if (! is_string($command)) {
            return [];
        }

        return [
            'request_id' => $this->extractSerializedScalar($command, 'requestId'),
            'trace_id' => $this->extractSerializedScalar($command, 'traceId'),
            'tenant_code' => $this->extractSerializedScalar($command, 'tenantCode'),
            'tenant_id' => $this->extractSerializedInt($command, 'tenantId'),
            'user_id' => $this->extractSerializedInt($command, 'userId'),
            'oauth_client_id' => $this->extractSerializedInt($command, 'oauthClientId'),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractJobUuid(array $payload): ?string
    {
        $uuid = $payload['uuid'] ?? null;

        return is_string($uuid) ? $uuid : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractBatchId(array $payload): ?string
    {
        $batchId = $payload['batchId'] ?? null;

        return is_string($batchId) ? $batchId : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractJobClass(array $payload): string
    {
        $displayName = $payload['displayName'] ?? null;

        if (is_string($displayName) && $displayName !== '') {
            return $displayName;
        }

        $job = $payload['job'] ?? null;

        if (is_string($job) && $job !== '') {
            return $job;
        }

        return 'unknown';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractMaxTries(array $payload): ?int
    {
        $maxTries = $payload['maxTries'] ?? null;

        return is_int($maxTries) ? $maxTries : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function extractNullableString(array $context, string $key): ?string
    {
        $value = $context[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function extractNullableInt(array $context, string $key): ?int
    {
        $value = $context[$key] ?? null;

        return is_int($value) ? $value : null;
    }

    private function truncateExceptionMessage(Throwable $exception): string
    {
        return mb_substr($exception->getMessage(), 0, 5000);
    }

    private function extractSerializedScalar(string $serializedCommand, string $property): ?string
    {
        $pattern = sprintf('/%s";s:\d+:"([^"]*)"/', preg_quote($property, '/'));

        if (preg_match($pattern, $serializedCommand, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function extractSerializedInt(string $serializedCommand, string $property): ?int
    {
        $pattern = sprintf('/%s";i:(\d+);/', preg_quote($property, '/'));

        if (preg_match($pattern, $serializedCommand, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function extractQueuedJobClass(JobQueued $event, array $payload): string
    {
        if (isset($event->job) && is_object($event->job)) {
            return $event->job::class;
        }

        $displayName = $payload['displayName'] ?? null;

        if (is_string($displayName) && $displayName !== '') {
            return $displayName;
        }

        $commandName = $payload['data']['commandName'] ?? null;

        if (is_string($commandName) && $commandName !== '') {
            return $commandName;
        }

        $job = $payload['job'] ?? null;

        if (is_string($job) && $job !== '') {
            return $job;
        }

        return 'unknown';
    }
}