<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

final readonly class TenantProvisioningResult
{
    /**
     * @param  array<int, string>  $missingTables
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public string $tenantCode,
        public string $schemaName,
        public bool $success,
        public bool $schemaExists,
        public bool $migrated,
        public array $missingTables = [],
        public array $errors = [],
    ) {}

    /**
     * @param  array<int, string>  $missingTables
     * @param  array<int, string>  $errors
     */
    public static function make(
        string $tenantCode,
        string $schemaName,
        bool $success,
        bool $schemaExists,
        bool $migrated,
        array $missingTables = [],
        array $errors = [],
    ): self {
        return new self(
            tenantCode: $tenantCode,
            schemaName: $schemaName,
            success: $success,
            schemaExists: $schemaExists,
            migrated: $migrated,
            missingTables: $missingTables,
            errors: $errors,
        );
    }

    public function failed(): bool
    {
        return ! $this->success;
    }

    /**
     * @return array{
     *     tenant_code: string,
     *     schema_name: string,
     *     success: bool,
     *     schema_exists: bool,
     *     migrated: bool,
     *     missing_tables: array<int, string>,
     *     errors: array<int, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'tenant_code' => $this->tenantCode,
            'schema_name' => $this->schemaName,
            'success' => $this->success,
            'schema_exists' => $this->schemaExists,
            'migrated' => $this->migrated,
            'missing_tables' => $this->missingTables,
            'errors' => $this->errors,
        ];
    }
}
