<?php

declare(strict_types=1);

namespace Tests\Support\Queue;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class FailingTestQueueJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct()
    {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(): void
    {
        throw new Exception('Falha de fila intencional para teste.');
    }
}