<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

final class IpRangeMatcher
{
    /**
     * @param  list<string>|null  $allowedRanges
     */
    public function ipIsAllowed(?string $ip, ?array $allowedRanges): bool
    {
        if ($allowedRanges === null || $allowedRanges === [] || $ip === null) {
            return true;
        }

        foreach ($allowedRanges as $range) {
            if ($this->ipMatchesRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    public function allowedIpRanges(mixed $ranges): ?array
    {
        if (! is_array($ranges)) {
            return null;
        }

        return array_values(array_filter($ranges, static fn (mixed $range): bool => is_string($range) && $range !== ''));
    }

    private function ipMatchesRange(string $ip, string $range): bool
    {
        if ($range === $ip) {
            return true;
        }

        if (! str_contains($range, '/')) {
            return false;
        }

        [$subnet, $prefix] = explode('/', $range, 2);

        if (! ctype_digit($prefix)) {
            return false;
        }

        $prefixLength = (int) $prefix;
        $ipBinary = @inet_pton($ip);
        $subnetBinary = @inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $maxPrefix = strlen($ipBinary) * 8;

        if ($prefixLength < 0 || $prefixLength > $maxPrefix) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($subnetBinary, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($ipBinary[$fullBytes]) & $mask) === (ord($subnetBinary[$fullBytes]) & $mask);
    }
}
