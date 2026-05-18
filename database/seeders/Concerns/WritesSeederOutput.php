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
        $property = new ReflectionProperty(Seeder::class, 'command');
        $property->setAccessible(true);

        if (! $property->isInitialized($this)) {
            return null;
        }

        $command = $property->getValue($this);

        return $command instanceof Command ? $command : null;
    }
}
