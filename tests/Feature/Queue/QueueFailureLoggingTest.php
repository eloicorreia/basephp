<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use Illuminate\Support\Facades\Queue;
use Tests\Support\Queue\FailingTestQueueJob;
use Tests\TestCase;

final class QueueFailureLoggingTest extends TestCase
{
    public function test_it_must_log_failed_job_event(): void
    {
        Queue::push(new FailingTestQueueJob());

        $this->assertDatabaseHas('queue_job_logs', [
            'event_type' => 'dispatched',
            'status' => 'queued',
            'job_class' => FailingTestQueueJob::class,
        ]);
    }
}