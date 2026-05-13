<?php

declare(strict_types=1);

namespace App\Services\Billing;

final class InvoiceSyncService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function sync(array $payload): void
    {
        // Extension point for invoice synchronization integrations.
    }
}
