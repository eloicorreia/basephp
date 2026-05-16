<?php

declare(strict_types=1);

namespace App\DTO\Admin;

final readonly class AssignUserRoleDTO
{
    public function __construct(
        public int $roleId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            roleId: (int) $data['role_id'],
        );
    }
}
