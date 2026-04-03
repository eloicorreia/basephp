<?php

declare(strict_types=1);

namespace App\Listeners\Queue;

use App\Services\Queue\QueueExecutionLogService;
use Illuminate\Queue\Events\JobProcessed;

final readonly class LogProcessedJob
{
    public function __construct(
        private QueueExecutionLogService $service,
    ) {
    }

    public function handle(JobProcessed $event): void
    {
        $this->service->logProcessed($event);
    }
}