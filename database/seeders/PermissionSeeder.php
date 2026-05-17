<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use (&$created, &$updated): void {
            foreach (PermissionRegistry::definitions() as $code => $definition) {
                $permission = Permission::query()->firstOrNew(['code' => $code]);
                $exists = $permission->exists;

                $permission->fill([
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'group' => $definition['group'],
                    'context' => $definition['context'],
                    'is_sensitive' => $definition['is_sensitive'],
                    'is_system' => true,
                    'active' => true,
                ]);

                if (! $exists) {
                    $created++;
                    $permission->save();

                    continue;
                }

                if ($permission->isDirty()) {
                    $updated++;
                    $permission->save();
                }
            }
        });

        $this->command?->info(sprintf(
            'Permissões sincronizadas. Criadas: %d. Atualizadas: %d.',
            $created,
            $updated
        ));
    }
}
