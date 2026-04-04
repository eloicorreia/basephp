<?php

declare(strict_types=1);

namespace App\DTOs\Mail;

final readonly class EmailAddressData
{
    public function __construct(
        public string $email,
        public ?string $name = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}