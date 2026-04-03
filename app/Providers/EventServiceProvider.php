<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\Queue\LogFailedJob;
use App\Listeners\Queue\LogJobExceptionOccurred;
use App\Listeners\Queue\LogProcessedJob;
use App\Listeners\Queue\LogProcessingJob;
use App\Listeners\Queue\LogQueuedJob;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        JobQueued::class => [
            LogQueuedJob::class,
        ],
        JobProcessing::class => [
            LogProcessingJob::class,
        ],
        JobProcessed::class => [
            LogProcessedJob::class,
        ],
        JobFailed::class => [
            LogFailedJob::class,
        ],
        JobExceptionOccurred::class => [
            LogJobExceptionOccurred::class,
        ],
    ];
}