<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->shouldSeedDevelopmentTenant()) {
            $this->command->warn(
                'Tenant de desenvolvimento não criado: ambiente não local/testing e sem permissão explícita.'
            );

            return;
        }

        DB::transaction(function (): void {
            $tenant = Tenant::query()->firstOrNew([
                'code' => (string) config('bootstrap.development_tenant.code', 'tenant-dev-001'),
            ]);

            if (! $tenant->exists) {
                $tenant->forceFill(['uuid' => (string) Str::uuid()]);
            }

            $tenant->fill([
                'name' => (string) config('bootstrap.development_tenant.name', 'Tenant Desenvolvimento 001'),
                'schema_name' => (string) config('bootstrap.development_tenant.schema_name', 'tenant_dev_001'),
                'status' => Tenant::STATUS_ACTIVE,
            ]);

            $tenant->save();
        });

        $this->command->info('Tenant de desenvolvimento sincronizado sem provisionar schema ou migrations tenant.');
    }

    private function shouldSeedDevelopmentTenant(): bool
    {
        $configuredValue = config('bootstrap.development_tenant.enabled');

        if (app()->environment(['local', 'testing'])) {
            return $configuredValue !== false;
        }

        return $configuredValue === true;
    }
}
