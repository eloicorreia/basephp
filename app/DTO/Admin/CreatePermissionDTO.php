<?php

declare(strict_types=1);

namespace App\DTO\Admin;

final readonly class CreatePermissionDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public ?string $group,
        public string $context,
        public bool $isSensitive,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) $data['code'],
            name: (string) $data['name'],
            description: isset($data['description']) ? (string) $data['description'] : null,
            group: isset($data['group']) ? (string) $data['group'] : null,
            context: (string) $data['context'],
            isSensitive: (bool) ($data['is_sensitive'] ?? false),
        );
    }
}
