<?php

declare(strict_types=1);

namespace App\Jobs\Mail;

use App\DTO\Mail\SendEmailData;
use App\Models\EmailDispatchLog;
use App\Models\Tenant;
use App\Services\Mail\Contracts\RuntimeMailSenderInterface;
use App\Services\Mail\EmailDispatchLogService;
use App\Services\Mail\TenantMailConfigResolverService;
use App\Services\Tenant\TenantExecutionManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class SendTenantEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $emailDispatchLogId,
        public readonly SendEmailData $emailData,
    ) {
        $this->onQueue('emails');
    }

    public function handle(
        TenantExecutionManager $tenantExecutionManager,
        TenantMailConfigResolverService $configResolverService,
        RuntimeMailSenderInterface $runtimeMailSender,
        EmailDispatchLogService $emailDispatchLogService,
    ): void {
        $tenant = Tenant::query()->findOrFail($this->tenantId);
        $log = EmailDispatchLog::query()->findOrFail($this->emailDispatchLogId);

        $tenantExecutionManager->run($tenant, function () use (
            $log,
            $configResolverService,
            $runtimeMailSender,
            $emailDispatchLogService
        ): void {
            $config = $configResolverService->resolveDefault();

            $jobUuid = null;

            if ($this->job !== null && method_exists($this->job, 'uuid')) {
                $jobUuid = $this->job->uuid();
            }

            $emailDispatchLogService->markSending(
                log: $log,
                config: $config,
                queueName: (string) $this->queue,
                queueConnection: (string) $this->connection,
                jobUuid: $jobUuid,
            );

            try {
                $result = $runtimeMailSender->send($config, $this->emailData);

                $emailDispatchLogService->markSent(
                    log: $log,
                    providerMessageId: $result['provider_message_id'] ?? null,
                );
            } catch (Throwable $throwable) {
                $emailDispatchLogService->markFailed($log, $throwable);
                throw $throwable;
            }
        });
    }
}