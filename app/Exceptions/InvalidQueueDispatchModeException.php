<?php

declare(strict_types=1);

namespace App\Exceptions\Queue;

use RuntimeException;

final class InvalidQueueDispatchModeException extends RuntimeException
{
    public static function forImmediateDispatchInsideTransactionalFlow(
        string $jobClass
    ): self {
        return new self(
            sprintf(
                'O job [%s] não pode ser dispatchado antes do commit em um fluxo transacional sem autorização explícita.',
                $jobClass
            )
        );
    }
}