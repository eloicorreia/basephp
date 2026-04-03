<?php

declare(strict_types=1);

namespace App\Listeners\Queue;

use App\Services\Queue\QueueExecutionLogService;
use Illuminate\Queue\Events\JobExceptionOccurred;

final readonly class LogJobExceptionOccurred
{
    public function __construct(
        private QueueExecutionLogService $service,
    ) {
    }

    public function handle(JobExceptionOccurred $event): void
    {
        $this->service->logException($event);
    }
}