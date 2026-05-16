<?php

declare(strict_types=1);

namespace Database\Seeders\Public;

use Database\Seeders\AdminMenuSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminMenuSeeder::class,
            TenantSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
