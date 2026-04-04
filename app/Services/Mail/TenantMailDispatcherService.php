<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\DTOs\Mail\SendEmailData;
use App\Jobs\Mail\SendTenantEmailJob;
use App\Models\EmailDispatchLog;
use App\Services\Tenant\TenantExecutionManager;
use App\Support\TenantContext;

final readonly class TenantMailDispatcherService
{
    public function __construct(
        private TenantContext $tenantContext,
        private EmailDispatchLogService $emailDispatchLogService,
    ) {
    }

    public function dispatch(SendEmailData $emailData, ?int $userId = null): EmailDispatchLog
    {
        $tenant = $this->tenantContext->require();

        $log = $this->emailDispatchLogService->createQueuedLog(
            email: $emailData,
            userId: $userId,
        );

        SendTenantEmailJob::dispatch(
            tenantId: (int) $tenant->id,
            emailDispatchLogId: (int) $log->id,
            emailData: $emailData,
        );

        return $log;
    }
}