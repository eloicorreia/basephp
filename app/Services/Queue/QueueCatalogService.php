<?php

declare(strict_types=1);

namespace App\Services\Queue;

final readonly class QueueCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            [
                'name' => 'high',
                'purpose' => 'Processamentos prioritários.',
                'retry_limit' => 3,
                'timeout_seconds' => 120,
            ],
            [
                'name' => 'default',
                'purpose' => 'Processamentos padrão.',
                'retry_limit' => 3,
                'timeout_seconds' => 120,
            ],
            [
                'name' => 'notifications',
                'purpose' => 'Notificações assíncronas.',
                'retry_limit' => 3,
                'timeout_seconds' => 120,
            ],
            [
                'name' => 'integrations',
                'purpose' => 'Integrações com sistemas externos.',
                'retry_limit' => 5,
                'timeout_seconds' => 300,
            ],
            [
                'name' => 'imports',
                'purpose' => 'Importações de dados.',
                'retry_limit' => 5,
                'timeout_seconds' => 300,
            ],
            [
                'name' => 'maintenance',
                'purpose' => 'Rotinas operacionais e manutenção.',
                'retry_limit' => 1,
                'timeout_seconds' => 120,
            ],
        ];
    }
}