<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use ReflectionProperty;

trait WritesSeederOutput
{
    private function seederInfo(string $message): void
    {
        $command = $this->seederCommand();

        if ($command instanceof Command) {
            $command->info($message);
        }
    }

    private function seederWarn(string $message): void
    {
        $command = $this->seederCommand();

        if ($command instanceof Command) {
            $command->warn($message);
        }
    }

    private function seederCommand(): ?Command
    {
        // Seeder::$command is a typed property that may be uninitialized when a
        // seeder is instantiated and run directly by tests or application code.
        // Reflection lets us detect that state without triggering a typed
        // property access error before falling back to silent output.
        $property = new ReflectionProperty(Seeder::class, 'command');
        $property->setAccessible(true);

        if (! $property->isInitialized($this)) {
            return null;
        }

        $command = $property->getValue($this);

        return $command instanceof Command ? $command : null;
    }
}
