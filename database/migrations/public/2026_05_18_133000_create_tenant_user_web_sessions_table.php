<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user_web_sessions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_id', 120);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason', 120)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'session_id'], 'tenant_user_web_sessions_unique_session');
            $table->index(['tenant_id', 'user_id', 'revoked_at'], 'tenant_user_web_sessions_user_active_idx');
            $table->index('session_id', 'tenant_user_web_sessions_session_idx');
            $table->index('last_activity_at', 'tenant_user_web_sessions_last_activity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_web_sessions');
    }
};
