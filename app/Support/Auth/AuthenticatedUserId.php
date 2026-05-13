<?php

declare(strict_types=1);

namespace App\Support\Auth;

final class AuthenticatedUserId
{
    public static function resolve(): ?int
    {
        return self::normalize(auth()->id());
    }

    public static function normalize(int|string|null $id): ?int
    {
        if (is_int($id)) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id)) {
            return (int) $id;
        }

        return null;
    }
}
