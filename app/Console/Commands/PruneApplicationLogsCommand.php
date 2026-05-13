<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PruneApplicationLogsCommand extends Command
{
    protected $signature = 'logs:prune {--dry-run : Report how many rows would be pruned without deleting them}';

    protected $description = 'Prune database-backed operational logs according to configured retention windows.';

    public function handle(): int
    {
        if (! (bool) config('observability.retention.enabled', true)) {
            $this->components->info('Pruning de logs desabilitado por configuração.');

            return self::SUCCESS;
        }

        $tables = config('observability.retention.public_tables', []);

        if (! is_array($tables) || $tables === []) {
            $this->components->warn('Nenhuma tabela configurada para retenção de logs.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach ($tables as $table => $settings) {
            if (! is_array($settings)) {
                continue;
            }

            $days = (int) ($settings['days'] ?? 0);
            if ($days <= 0) {
                continue;
            }

            $column = (string) ($settings['column'] ?? 'created_at');
            $this->assertValidIdentifier((string) $table);
            $this->assertValidIdentifier($column);

            $cutoff = now()->subDays($days);
            $query = DB::table((string) $table)->where($column, '<', $cutoff);
            $count = (int) (clone $query)->count();

            if (! $dryRun && $count > 0) {
                $query->delete();
            }

            $total += $count;

            $this->line(sprintf(
                '%s: %d registro(s) %s antes de %s.',
                (string) $table,
                $count,
                $dryRun ? 'encontrado(s)' : 'removido(s)',
                $cutoff->toDateTimeString()
            ));
        }

        $this->components->info(sprintf(
            '%d registro(s) %s pela política de retenção.',
            $total,
            $dryRun ? 'seriam removidos' : 'removidos'
        ));

        return self::SUCCESS;
    }

    private function assertValidIdentifier(string $identifier): void
    {
        if (! preg_match('/^[a-z][a-z0-9_]{0,62}$/', $identifier)) {
            throw new InvalidArgumentException('Identificador de tabela ou coluna inválido.');
        }
    }
}
