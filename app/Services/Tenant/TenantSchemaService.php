<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TenantSchemaService
{
    public function createSchema(string $schemaName): void
    {
        $this->assertValidSchemaName($schemaName);

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schemaName));
    }

    public function setSearchPath(string $schemaName): void
    {
        $this->assertValidSchemaName($schemaName);

        DB::statement(sprintf('SET search_path TO "%s", public', $schemaName));
    }

    public function resetSearchPath(): void
    {
        DB::statement('SET search_path TO public');
    }

    private function assertValidSchemaName(string $schemaName): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]{2,62}$/', $schemaName)) {
            throw new InvalidArgumentException('Nome de schema inválido.');
        }
    }
}