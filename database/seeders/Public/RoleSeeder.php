<?php

declare(strict_types=1);

namespace Database\Seeders\Public;

use App\Enums\RoleCode;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}