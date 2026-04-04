<?php

declare(strict_types=1);

namespace App\DTO\Mail;

final readonly class SendEmailData
{
    /**
     * @param array<int, EmailAddressData> $to
     * @param array<int, EmailAddressData> $cc
     * @param array<int, EmailAddressData> $bcc
     * @param array<string, mixed>|null $context
     */
    public function __construct(
        public string $trigger,
        public string $subject,
        public ?string $htmlBody,
        public ?string $textBody,
        public array $to,
        public array $cc = [],
        public array $bcc = [],
        public ?string $idempotencyKey = null,
        public ?array $context = null,
    ) {
    }
}