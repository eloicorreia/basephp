<?php

declare(strict_types=1);

namespace App\Listeners\Queue;

use App\Services\Queue\QueueExecutionLogService;
use Illuminate\Queue\Events\JobQueued;

final readonly class LogQueuedJob
{
    public function __construct(
        private QueueExecutionLogService $service,
    ) {
    }

    public function handle(JobQueued $event): void
    {
        $this->service->logQueued($event);
    }
}