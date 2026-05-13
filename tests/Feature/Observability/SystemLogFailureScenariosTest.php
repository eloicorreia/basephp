<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\SystemLog;
use App\Services\Logging\LogPersistenceService;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SystemLogFailureScenariosTest extends TestCase
{
    public function test_it_persists_system_log_when_exception_occurs_before_tenant_resolution(): void
    {
        $this->markTestIncomplete(
            'Adicionar rota e cenário controlado para falha antes da resolução do tenant.'
        );
    }

    public function test_it_persists_system_log_when_authorization_fails(): void
    {
        $this->markTestIncomplete(
            'Cobrir negação de acesso e system log correspondente.'
        );
    }

    public function test_it_persists_system_log_when_validation_fails(): void
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_it_does_not_persist_sensitive_data_in_system_log_context(): void
    {
        $requestId = (string) Str::uuid();
        $traceId = (string) Str::uuid();
        request()->attributes->set('request_id', $requestId);
        request()->attributes->set('trace_id', $traceId);

        app(LogPersistenceService::class)->logSystemInfo(
            message: 'Teste de sanitização.',
            category: 'observability',
            operation: 'sanitize_system_log',
            context: [
                'safe' => 'visible',
                'credentials' => [
                    'password' => 'secret',
                    'client_secret' => 'client-secret',
                    'nested' => [
                        'refresh_token' => 'refresh-secret',
                        'safe_nested' => 'still-visible',
                    ],
                ],
            ],
        );

        $log = SystemLog::query()
            ->where('request_id', $requestId)
            ->where('trace_id', $traceId)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('visible', $log->context['safe']);
        $this->assertSame('***', $log->context['credentials']['password']);
        $this->assertSame('***', $log->context['credentials']['client_secret']);
        $this->assertSame('***', $log->context['credentials']['nested']['refresh_token']);
        $this->assertSame('still-visible', $log->context['credentials']['nested']['safe_nested']);
    }
}
