<?php

declare(strict_types=1);

namespace App\Jobs\Base;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Jobs\Contracts\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class AbstractTenantAwareJob implements ShouldQueue, TenantAwareJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use InteractsWithTenantContext;

    public function __construct(
        int $tenantId,
        ?string $requestId = null,
        ?string $traceId = null,
        ?int $userId = null,
        ?int $oauthClientId = null
    ) {
        $this->initializeTenantContextData(
            tenantId: $tenantId,
            requestId: $requestId,
            traceId: $traceId,
            userId: $userId,
            oauthClientId: $oauthClientId
        );
    }
}