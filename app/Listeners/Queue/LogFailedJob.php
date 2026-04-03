<?php

declare(strict_types=1);

namespace App\Listeners\Queue;

use App\Services\Queue\QueueExecutionLogService;
use Illuminate\Queue\Events\JobFailed;

final readonly class LogFailedJob
{
    public function __construct(
        private QueueExecutionLogService $service,
    ) {
    }

    public function handle(JobFailed $event): void
    {
        $this->service->logFailed($event);
    }
}