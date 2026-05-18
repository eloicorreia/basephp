<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Logging\LogPersistenceService;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SystemUpdateCommand extends Command
{
    protected $signature = 'system:update
        {--force : Executar operações em modo force}
        {--skip-tenants : Não migrar tenants}
        {--skip-validate : Não validar tenants}
        {--only-active : Aplicar operações apenas em tenants ativos}';

    protected $description = 'Executa atualização operacional do sistema em ordem segura para produção.';

    public function __construct(
        private readonly LogPersistenceService $logPersistenceService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        if (! $this->runStep('Running public migrations', 'migrate', ['--force' => $force])) {
            return self::FAILURE;
        }

        if (! $this->runPermissionSyncIfAvailable()) {
            return self::FAILURE;
        }

        if (! $this->runStep('Running AdminMenuSeeder', 'db:seed', [
            '--class' => AdminMenuSeeder::class,
            '--force' => true,
        ])) {
            return self::FAILURE;
        }

        if (! (bool) $this->option('skip-tenants')) {
            $tenantMigrateParameters = ['--force' => true];

            if ((bool) $this->option('only-active')) {
                $tenantMigrateParameters['--only-active'] = true;
            }

            if (! $this->runStep('Running tenant migrations', 'tenants:migrate', $tenantMigrateParameters)) {
                return self::FAILURE;
            }
        } else {
            $this->warn('Skipping tenant migrations by --skip-tenants.');
        }

        if (! (bool) $this->option('skip-validate')) {
            $tenantValidateParameters = [];

            if ((bool) $this->option('only-active')) {
                $tenantValidateParameters['--only-active'] = true;
            }

            if (! $this->runStep('Validating tenants', 'tenants:validate', $tenantValidateParameters)) {
                return self::FAILURE;
            }
        } else {
            $this->warn('Skipping tenant validation by --skip-validate.');
        }

        if (! $this->runStep('Clearing optimized cache', 'optimize:clear', [])) {
            return self::FAILURE;
        }

        $this->info('System update finished successfully.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function runStep(string $label, string $command, array $parameters): bool
    {
        $this->line('');
        $this->line($label);

        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = trim(Artisan::output());

            if ($output !== '') {
                $this->line($output);
            }

            if ($exitCode !== self::SUCCESS) {
                $this->logFailure($command, sprintf('Command returned exit code %d.', $exitCode));
                $this->error(sprintf('FAILED: Command returned exit code %d.', $exitCode));

                return false;
            }

            $this->info('OK');

            return true;
        } catch (Throwable $throwable) {
            $this->logFailure($command, $throwable->getMessage(), $throwable);
            $this->error('FAILED: '.$throwable->getMessage());

            return false;
        }
    }

    private function runPermissionSyncIfAvailable(): bool
    {
        $availableCommands = Artisan::all();

        foreach (['permissions:sync', 'permission:sync'] as $commandName) {
            if (array_key_exists($commandName, $availableCommands)) {
                return $this->runStep('Running permission sync', $commandName, ['--force' => true]);
            }
        }

        $this->warn('Permission sync command not found; skipping explicit permission sync.');

        return $this->runStep('Running PermissionSeeder', 'db:seed', [
            '--class' => PermissionSeeder::class,
            '--force' => true,
        ]);
    }

    private function logFailure(string $command, string $message, ?Throwable $throwable = null): void
    {
        $context = [
            'command' => $command,
            'message' => $message,
        ];

        if ($throwable !== null) {
            $context['error_class'] = $throwable::class;
        }

        try {
            $this->logPersistenceService->logSystemWarning(
                message: $message,
                category: 'tenant-operations',
                operation: 'system_update',
                context: $context,
                processingStatus: 'failed',
            );
        } catch (Throwable $loggingThrowable) {
            Log::warning('Falha ao persistir log operacional de system:update.', [
                'command' => $command,
                'error_class' => $loggingThrowable::class,
                'message' => $loggingThrowable->getMessage(),
            ]);
        }
    }
}
