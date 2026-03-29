<?php

declare(strict_types=1);

namespace App\DTOs\Admin;

final class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly int $roleId,
        public readonly bool $isActive,
        public readonly bool $mustChangePassword,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            email: (string) $data['email'],
            password: (string) $data['password'],
            roleId: (int) $data['role_id'],
            isActive: (bool) ($data['is_active'] ?? true),
            mustChangePassword: (bool) ($data['must_change_password'] ?? true),
        );
    }
}