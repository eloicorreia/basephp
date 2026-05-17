<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $adminRole = Role::query()->updateOrCreate(
                ['code' => 'admin'],
                [
                    'name' => 'Administrador',
                    'active' => true,
                ]
            );

            Role::query()->updateOrCreate(
                ['code' => 'user'],
                [
                    'name' => 'Usuário',
                    'active' => true,
                ]
            );

            $adminFullPermission = Permission::query()
                ->where('code', PermissionRegistry::ADMIN_FULL)
                ->first();

            if (! $adminFullPermission instanceof Permission) {
                throw new RuntimeException(
                    'Permissão admin.full não encontrada. Execute PermissionSeeder antes de RoleSeeder.'
                );
            }

            $adminRole->permissions()->syncWithoutDetaching([
                $adminFullPermission->id => [
                    'assigned_by' => null,
                    'assigned_at' => now(),
                ],
            ]);
        });

        $this->command?->info('Roles base sincronizadas com sucesso.');
    }
}
