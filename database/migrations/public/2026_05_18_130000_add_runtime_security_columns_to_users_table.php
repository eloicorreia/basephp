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
            $table->unsignedSmallInteger('failed_login_attempts')
                ->default(0)
                ->after('last_login_ip');
            $table->timestamp('locked_until')
                ->nullable()
                ->after('failed_login_attempts');
            $table->boolean('locked_by_admin')
                ->default(false)
                ->after('locked_until');
            $table->timestamp('password_changed_at')
                ->nullable()
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
