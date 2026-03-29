<?php

declare(strict_types=1);

namespace Database\Seeders\Public;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('code', RoleCode::ADMIN->value)
            ->firstOrFail();

        $tenant = Tenant::query()
            ->where('code', 'tenant-dev-001')
            ->firstOrFail();

        $user = User::query()->updateOrCreate(
            ['email' => 'adminnfe@local.test'],
            [
                'name' => 'adminnfe',
                'password' => Hash::make('adminnfe'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        TenantUser::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ],
            [
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );
    }
}