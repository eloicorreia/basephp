<?php

declare(strict_types=1);

namespace Database\Seeders\Public;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->updateOrCreate(
            ['code' => 'tenant-dev-001'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Tenant Desenvolvimento 001',
                'schema_name' => 'tenant_dev_001',
                'status' => 'active',
            ]
        );
    }
}