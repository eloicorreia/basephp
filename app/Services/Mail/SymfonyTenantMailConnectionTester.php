<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\DTO\Mail\TenantMailConfigData;
use App\Exceptions\Mail\TenantMailConnectionException;
use App\Services\Mail\Contracts\TenantMailConnectionTesterInterface;
use Throwable;

final readonly class SymfonyTenantMailConnectionTester implements TenantMailConnectionTesterInterface
{
    public function __construct(
        private RuntimeMailTransportFactory $transportFactory,
    ) {}

    public function test(TenantMailConfigData $config): void
    {
        $transport = $this->transportFactory->make($config);

        try {
            $transport->start();
            $transport->stop();
        } catch (Throwable) {
            throw new TenantMailConnectionException;
        }
    }
}
