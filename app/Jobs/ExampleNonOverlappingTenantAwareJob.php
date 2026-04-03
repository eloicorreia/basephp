<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Base\AbstractTenantAwareJob;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class ExampleNonOverlappingTenantAwareJob extends AbstractTenantAwareJob
{
    public int $tries = 10;

    public int $timeout = 180;

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

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                sprintf(
                    'tenant:%d:resource:%d',
                    $this->getTenantId(),
                    $this->resourceId
                )
            ))->releaseAfter(60),
        ];
    }

    public function backoff(): array
    {
        return [15, 30, 60, 120];
    }

    public function handle(): void
    {
        $this->runInTenantContext(function (): void {
            // Executar operação sem sobreposição simultânea.
        });
    }
}