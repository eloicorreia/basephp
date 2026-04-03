<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Base\AbstractTenantAwareJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;

final class ExampleUniqueTenantAwareJob extends AbstractTenantAwareJob implements ShouldBeUnique
{
    public int $tries = 1;

    public int $timeout = 120;

    public int $uniqueFor = 900;

    public function __construct(
        int $tenantId,
        private readonly int $resourceId,
        ?string $requestId = null,
        ?string $traceId = null,
        ?int $userId = null,
        ?int $oauthClientId = null
    ) {
        parent::__construct(
            tenantId: $tenantId,
            requestId: $requestId,
            traceId: $traceId,
            userId: $userId,
            oauthClientId: $oauthClientId
        );

        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function uniqueId(): string
    {
        return sprintf(
            'tenant:%d:resource:%d',
            $this->getTenantId(),
            $this->resourceId
        );
    }

    public function handle(): void
    {
        $this->runInTenantContext(function (): void {
            // Executar operação única.
        });
    }
}