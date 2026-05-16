<?php

declare(strict_types=1);

namespace App\DTO\Admin;

final readonly class SyncRolePermissionsDTO
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function __construct(
        public array $permissionIds,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            permissionIds: array_values(array_map('intval', $data['permission_ids'] ?? [])),
        );
    }
}
