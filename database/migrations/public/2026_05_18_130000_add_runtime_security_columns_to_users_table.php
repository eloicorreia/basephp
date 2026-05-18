<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Legacy/global lock columns kept for compatibility. Tenant security policy stores
            // per-tenant state in tenant_user_security_states and must not read these fields.
            $table->unsignedSmallInteger('failed_login_attempts')
                ->default(0)
                ->comment('Legacy/global lock field; tenant policy uses tenant_user_security_states.')
                ->after('last_login_ip');
            $table->timestamp('locked_until')
                ->nullable()
                ->comment('Legacy/global lock field; tenant policy uses tenant_user_security_states.')
                ->after('failed_login_attempts');
            $table->boolean('locked_by_admin')
                ->default(false)
                ->comment('Legacy/global lock field; tenant policy uses tenant_user_security_states.')
                ->after('locked_until');
            $table->timestamp('password_changed_at')
                ->nullable()
                ->comment('Global password-change timestamp intentionally kept on users.')
                ->after('locked_by_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'failed_login_attempts',
                'locked_until',
                'locked_by_admin',
                'password_changed_at',
            ]);
        });
    }
};
