<?php

declare(strict_types=1);

namespace App\Services\Auth;

final readonly class WebAdminLoginResult
{
    public function __construct(
        public bool $successful,
        public ?string $errorMessage = null,
        public bool $mustChangePassword = false,
    ) {}

    public static function success(bool $mustChangePassword = false): self
    {
        return new self(true, mustChangePassword: $mustChangePassword);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
