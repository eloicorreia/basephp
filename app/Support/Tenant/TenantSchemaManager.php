<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TenantSchemaManager
{
    public function activate(string $schemaName): void
    {
        $schema = trim($schemaName);

        if ($schema === '') {
            throw new InvalidArgumentException('Schema do tenant não pode ser vazio.');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $schema)) {
            throw new InvalidArgumentException('Schema do tenant possui formato inválido.');
        }

        DB::statement(sprintf('SET search_path TO "%s", public', $schema));
    }

    public function reset(): void
    {
        DB::statement('SET search_path TO public');
    }
}