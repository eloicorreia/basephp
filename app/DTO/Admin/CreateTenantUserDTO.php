<?php

declare(strict_types=1);

namespace App\DTO\Admin;

final readonly class CreateTenantUserDTO
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public int $roleId,
        public bool $isActive,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (int) $data['tenant_id'],
            userId: (int) $data['user_id'],
            roleId: (int) $data['role_id'],
            isActive: (bool) $data['is_active'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'role_id' => $this->roleId,
            'is_active' => $this->isActive,
        ];
    }
}