<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\DTO\Queue\DispatchContextDTO;
use App\Exceptions\InvalidQueueDispatchModeException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class QueueDispatchService
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {
    }

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

        if (is_object($pendingDispatch) && method_exists($pendingDispatch, 'afterCommit')) {
            if ($context->afterCommit) {
                return $pendingDispatch->afterCommit();
            }

            return $pendingDispatch->beforeCommit();
        }

        return $pendingDispatch;
    }
}