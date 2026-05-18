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
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'failed_login_attempts') ? 'failed_login_attempts' : null,
                Schema::hasColumn('users', 'locked_until') ? 'locked_until' : null,
                Schema::hasColumn('users', 'locked_by_admin') ? 'locked_by_admin' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'failed_login_attempts')) {
                $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('last_login_ip');
            }

            if (! Schema::hasColumn('users', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            }

            if (! Schema::hasColumn('users', 'locked_by_admin')) {
                $table->boolean('locked_by_admin')->default(false)->after('locked_until');
            }
        });
    }
};
