<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Auth\PermissionRegistry;
use Database\Seeders\Concerns\WritesSeederOutput;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RoleSeeder extends Seeder
{
    use WritesSeederOutput;

    public function run(): void
    {
        DB::transaction(function (): void {
            $adminRole = Role::query()->updateOrCreate(
                ['code' => RoleCode::ADMIN->value],
                [
                    'name' => 'Administrador',
                    'active' => true,
                ]
            );

            Role::query()->updateOrCreate(
                ['code' => RoleCode::EMPRESA->value],
                [
                    'name' => 'Empresa',
                    'active' => true,
                ]
            );

            Role::query()->updateOrCreate(
                ['code' => RoleCode::USUARIO->value],
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

        $this->seederInfo('Roles base sincronizadas com sucesso.');
    }
}
