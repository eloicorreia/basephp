<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Services\Invoice\InvoiceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SyncInvoicesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use InteractsWithTenantContext;

    public function __construct(
        protected int|string $tenantId,
        private readonly array $payload,
    ) {
    }

    public function handle(InvoiceSyncService $service): void
    {
        $this->runInTenantContext(function () use ($service): void {
            $service->sync($this->payload);
        });
    }
}