<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Console\Command;

final class FailingPermissionSyncCommand extends Command
{
    protected $signature = 'permissions:sync {--force}';

    protected $description = 'Comando fake de teste para simular falha de sync de permissões.';

    public function handle(): int
    {
        $this->error('Permission sync failed intentionally.');

        return self::FAILURE;
    }
}
