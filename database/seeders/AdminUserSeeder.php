<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Concerns\WritesSeederOutput;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    use WritesSeederOutput;

    public function run(): void
    {
        DB::transaction(function (): void {
            $adminRole = Role::query()
                ->where('code', RoleCode::ADMIN->value)
                ->first();

            if (! $adminRole instanceof Role) {
                throw new RuntimeException('Role admin não encontrada. Execute RoleSeeder antes de AdminUserSeeder.');
            }

            $email = (string) config('bootstrap.admin_user.email', 'admin@example.com');
            $name = (string) config('bootstrap.admin_user.name', 'Administrador');
            $password = config('bootstrap.admin_user.password');

            $user = User::query()->where('email', $email)->first();

            if ($user instanceof User) {
                if ($user->role_id === null) {
                    $user->forceFill(['role_id' => $adminRole->id])->save();
                    $this->seederInfo('Usuário administrador existente recebeu a role admin por não possuir role.');
                } elseif ((int) $user->role_id !== (int) $adminRole->id) {
                    $this->seederWarn(
                        'Usuário administrador já existe com outra role. A role não foi sobrescrita automaticamente.'
                    );
                } else {
                    $this->seederInfo('Usuário administrador já existe. Senha e status preservados.');
                }

                return;
            }

            if (! is_string($password) || trim($password) === '') {
                if (app()->environment(['local', 'testing'])) {
                    $password = 'ChangeMe123!';
                } else {
                    throw new RuntimeException(
                        'ADMIN_USER_PASSWORD deve ser definido para criar o usuário administrador inicial fora de local/testing.'
                    );
                }
            }

            $admin = new User;
            $admin->forceFill([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ])->save();

            $this->seederInfo(sprintf('Usuário administrador inicial criado: %s.', $email));
        });
    }
}
