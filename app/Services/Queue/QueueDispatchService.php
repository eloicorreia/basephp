<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\DTO\Queue\DispatchContextDTO;
use App\Exceptions\Queue\InvalidQueueDispatchModeException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class QueueDispatchService
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {}

    public function dispatch(
        ShouldQueue $job,
        DispatchContextDTO $context,
        bool $allowBeforeCommit = false
    ): mixed {
        if ($context->afterCommit === false && $allowBeforeCommit === false) {
            throw InvalidQueueDispatchModeException::forImmediateDispatchInsideTransactionalFlow(
                $job::class
            );
        }

        if ($context->connectionName !== null && method_exists($job, 'onConnection')) {
            $job->onConnection($context->connectionName);
        }

        if ($context->queueName !== null && method_exists($job, 'onQueue')) {
            $job->onQueue($context->queueName);
        }

        $pendingDispatch = $this->dispatcher->dispatch($job);

        if ($context->afterCommit && is_object($pendingDispatch) && method_exists($pendingDispatch, 'afterCommit')) {
            return $pendingDispatch->afterCommit();
        }

        if (! $context->afterCommit && is_object($pendingDispatch) && method_exists($pendingDispatch, 'beforeCommit')) {
            return $pendingDispatch->beforeCommit();
        }

        return $pendingDispatch;
    }
}
