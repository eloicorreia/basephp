<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\InteractsWithTenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RebuildTenantCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use InteractsWithTenantContext;

    public function __construct(
        protected int|string $tenantId
    ) {
    }

    public function handle(): void
    {
        $this->runInTenantContext(function (): void {
            // Regra tenant-aware aqui.
        });
    }
}