<?php

declare(strict_types=1);

namespace App\DTO\Queue;

final readonly class DispatchContextDTO
{
    public function __construct(
        public bool $afterCommit = true,
        public ?string $requestId = null,
        public ?string $traceId = null,
        public ?int $userId = null,
        public ?int $oauthClientId = null,
        public ?string $operation = null,
        public ?string $queueName = null,
        public ?string $connectionName = null,
    ) {
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(): array
    {
        return [
            'after_commit' => $this->afterCommit,
            'request_id' => $this->requestId,
            'trace_id' => $this->traceId,
            'user_id' => $this->userId,
            'oauth_client_id' => $this->oauthClientId,
            'operation' => $this->operation,
            'queue_name' => $this->queueName,
            'connection_name' => $this->connectionName,
        ];
    }
}