<?php

declare(strict_types=1);

namespace App\Services\Mail\Contracts;

use App\DTOs\Mail\SendEmailData;
use App\DTOs\Mail\TenantMailConfigData;

interface RuntimeMailSenderInterface
{
    /**
     * @return array{provider_message_id: string|null}
     */
    public function send(TenantMailConfigData $config, SendEmailData $email): array;
}