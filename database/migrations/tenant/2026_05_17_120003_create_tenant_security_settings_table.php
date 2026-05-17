<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_security_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('session_lifetime_minutes')->default(120);
            $table->unsignedSmallInteger('idle_timeout_minutes')->nullable();
            $table->boolean('force_single_session_per_user')->default(false);
            $table->boolean('logout_on_password_change')->default(true);
            $table->unsignedSmallInteger('max_login_attempts')->default(5);
            $table->unsignedSmallInteger('lockout_duration_minutes')->default(15);
            $table->boolean('unlock_requires_admin')->default(false);
            $table->boolean('notify_user_on_failed_login')->default(true);
            $table->boolean('notify_admin_on_lockout')->default(true);
            $table->jsonb('allowed_ip_ranges')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_security_settings');
    }
};
