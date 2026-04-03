<?php

declare(strict_types=1);

namespace App\Jobs\Base;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Jobs\Contracts\TenantAwareJob;
use DateTimeInterface;
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

    protected int $tenantId;

    protected ?string $requestId;

    protected ?string $traceId;

    protected ?int $userId;

    protected ?int $oauthClientId;

    /**
     * Número padrão de tentativas.
     *
     * Todo job concreto pode sobrescrever este valor.
     */
    public int $tries = 3;

    /**
     * Tempo máximo de execução do job, em segundos.
     *
     * Todo job concreto pode sobrescrever este valor.
     */
    public int $timeout = 120;

    /**
     * Quantidade máxima de exceções não tratadas antes de falha definitiva.
     *
     * Todo job concreto pode sobrescrever este valor.
     */
    public int $maxExceptions = 3;

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

    /**
     * Backoff padrão entre tentativas, em segundos.
     *
     * Pode ser sobrescrito por jobs concretos.
     *
     * @return array<int, int>|int
     */
    public function backoff(): array|int
    {
        return [10, 30, 60];
    }

    /**
     * Prazo máximo absoluto para novas tentativas.
     *
     * Pode ser sobrescrito por jobs concretos.
     */
    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(15);
    }
}