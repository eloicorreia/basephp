<?php

declare(strict_types=1);

namespace App\Listeners\Queue;

use App\Services\Queue\QueueExecutionLogService;
use Illuminate\Queue\Events\JobProcessing;

final readonly class LogProcessingJob
{
    public function __construct(
        private QueueExecutionLogService $service,
    ) {
    }

    public function handle(JobProcessing $event): void
    {
        $this->service->logProcessing($event);
    }
}