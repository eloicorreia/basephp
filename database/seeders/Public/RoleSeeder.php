<?php

declare(strict_types=1);

namespace Database\Seeders\Public;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = [
                [
                    'code' => RoleCode::ADMIN->value,
                    'name' => 'Administrador',
                    'active' => true,
                ],
                [
                    'code' => RoleCode::EMPRESA->value,
                    'name' => 'Empresa',
                    'active' => true,
                ],
                [
                    'code' => RoleCode::USUARIO->value,
                    'name' => 'Usuário',
                    'active' => true,
                ],
            ];

            foreach ($roles as $role) {
                Role::query()->updateOrCreate(
                    ['code' => $role['code']],
                    $role
                );
            }

            $adminRole = Role::query()->where('code', RoleCode::ADMIN->value)->first();

            if (! $adminRole instanceof Role) {
                return;
            }

            $syncPayload = [];

            foreach (Permission::query()->whereIn('code', PermissionRegistry::codes())->pluck('id')->all() as $permissionId) {
                $syncPayload[$permissionId] = [
                    'assigned_at' => now(),
                ];
            }

            if ($syncPayload !== []) {
                $adminRole->permissions()->syncWithoutDetaching($syncPayload);
            }
        });
    }
}
