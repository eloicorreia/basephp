<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Queue;

use App\DTO\Queue\DispatchContextDTO;
use App\Jobs\TestQueueJob;
use App\Services\Queue\QueueDispatchService;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

final class QueueDispatchServiceTest extends TestCase
{
    public function test_it_must_dispatch_a_job(): void
    {
        Bus::fake();

        $service = app(QueueDispatchService::class);

        $service->dispatch(
            job: new TestQueueJob('dispatch test'),
            context: new DispatchContextDTO(
                afterCommit: true,
                queueName: 'default',
                connectionName: 'database'
            )
        );

        Bus::assertDispatched(TestQueueJob::class);
    }
}