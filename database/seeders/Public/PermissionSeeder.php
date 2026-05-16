<?php

declare(strict_types=1);

namespace Database\Seeders\Public;

use App\Models\Permission;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionRegistry::definitions() as $code => $definition) {
            Permission::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'context' => $definition['context'],
                    'is_sensitive' => $definition['is_sensitive'],
                    'active' => true,
                ]
            );
        }
    }
}
