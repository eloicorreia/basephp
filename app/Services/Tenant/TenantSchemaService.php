<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TenantSchemaService
{
    public function assertValidSchemaName(string $schemaName): void
    {
        if (! preg_match('/^[a-z][a-z0-9_]{2,62}$/', $schemaName)) {
            throw new InvalidArgumentException('Nome de schema inválido.');
        }
    }

    public function createSchema(string $schemaName): void
    {
        $this->assertValidSchemaName($schemaName);

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schemaName));
    }

    public function ensureMigrationRepository(string $schemaName): void
    {
        $this->assertValidSchemaName($schemaName);

        DB::statement(sprintf(
            'CREATE TABLE IF NOT EXISTS "%s"."migrations" (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )',
            $schemaName
        ));
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

    public function schemaExists(string $schemaName): bool
    {
        $this->assertValidSchemaName($schemaName);

        $row = DB::selectOne(
            'SELECT EXISTS (
                SELECT 1
                FROM information_schema.schemata
                WHERE schema_name = ?
            ) AS schema_exists',
            [$schemaName]
        );

        return filter_var($row?->schema_exists, FILTER_VALIDATE_BOOLEAN);
    }
}
