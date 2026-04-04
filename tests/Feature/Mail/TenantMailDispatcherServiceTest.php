<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\DTO\Mail\EmailAddressData;
use App\DTO\Mail\SendEmailData;
use App\Jobs\Mail\SendTenantEmailJob;
use App\Models\Tenant;
use App\Services\Logging\IntegrationLogger;
use App\Services\Logging\LogPersistenceService;
use App\Services\Mail\EmailDispatchLogService;
use App\Services\Mail\TenantMailDispatcherService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

final class TenantMailDispatcherServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_job_and_persists_queued_log(): void
    {
        Queue::fake();

        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-a',
            'name' => 'Tenant A',
            'schema_name' => 'tenant_a',
            'status' => 'active',
        ]);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($tenant);

        $integrationLogger = Mockery::mock(IntegrationLogger::class);
        $integrationLogger->shouldReceive('logRequest')
            ->once();

        $logPersistenceService = Mockery::mock(LogPersistenceService::class);

        $emailDispatchLogService = new EmailDispatchLogService(
            tenantContext: $tenantContext,
            logPersistenceService: $logPersistenceService,
            integrationLogger: $integrationLogger,
        );

        $service = new TenantMailDispatcherService(
            tenantContext: $tenantContext,
            emailDispatchLogService: $emailDispatchLogService,
        );

        $log = $service->dispatch(new SendEmailData(
            trigger: 'invoice.created',
            subject: 'Teste',
            htmlBody: '<p>Olá</p>',
            textBody: 'Olá',
            to: [
                new EmailAddressData('teste@example.com', 'Teste'),
            ],
        ));

        $this->assertDatabaseHas('email_dispatch_logs', [
            'id' => $log->id,
            'tenant_id' => $tenant->id,
            'tenant_code' => 'tenant-a',
            'status' => 'queued',
            'trigger' => 'invoice.created',
            'subject' => 'Teste',
        ]);

        Queue::assertPushed(SendTenantEmailJob::class, function (SendTenantEmailJob $job) use ($tenant, $log): bool {
            return $job->tenantId === (int) $tenant->id
                && $job->emailDispatchLogId === (int) $log->id
                && $job->emailData->trigger === 'invoice.created';
        });
    }
}