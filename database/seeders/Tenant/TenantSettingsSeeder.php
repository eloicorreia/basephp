<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('business_logs')->insert([
            'request_id' => null,
            'trace_id' => null,
            'user_id' => null,
            'category' => 'tenant',
            'operation' => 'bootstrap',
            'entity_type' => 'tenant',
            'entity_id' => null,
            'message' => 'Bootstrap inicial do tenant executado com sucesso.',
            'context' => json_encode([
                'seed' => static::class,
            ], JSON_THROW_ON_ERROR),
            'processing_status' => 'success',
            'created_at' => now(),
        ]);
    }
}