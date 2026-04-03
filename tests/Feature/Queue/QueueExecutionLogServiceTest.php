<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use App\Jobs\TestQueueJob;
use App\Models\QueueJobLog;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class QueueExecutionLogServiceTest extends TestCase
{
    public function test_it_must_log_queued_job(): void
    {
        Queue::push(new TestQueueJob('queue log test'));

        $this->assertDatabaseHas('queue_job_logs', [
            'event_type' => 'dispatched',
            'status' => 'queued',
            'job_class' => TestQueueJob::class,
        ]);
    }

    public function test_it_must_persist_sanitized_payload(): void
    {
        Queue::push(new TestQueueJob('queue log test'));

        $log = QueueJobLog::query()
            ->where('event_type', 'dispatched')
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertIsArray($log->input_payload);
    }
}