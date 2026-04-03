<?php

declare(strict_types=1);

namespace App\Jobs\Contracts;

interface TenantAwareJob
{
    public function getTenantId(): int;

    public function getRequestId(): ?string;

    public function getTraceId(): ?string;

    public function getUserId(): ?int;

    public function getOauthClientId(): ?int;

    /**
     * @return array<string, int|string|null>
     */
    public function getTechnicalContext(): array;
}