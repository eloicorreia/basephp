<?php

declare(strict_types=1);

namespace App\DTO\Mail;

final readonly class TenantMailConfigData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $driver,
        public string $host,
        public int $port,
        public ?string $encryption,
        public ?string $username,
        public ?string $password,
        public string $fromAddress,
        public string $fromName,
        public ?string $replyToAddress,
        public ?string $replyToName,
        public int $timeoutSeconds,
        public bool $verifyPeer,
        public bool $verifyPeerName,
        public bool $allowSelfSigned,
    ) {
    }
}