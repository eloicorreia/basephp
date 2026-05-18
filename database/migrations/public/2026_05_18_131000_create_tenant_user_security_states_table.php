<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user_security_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable()->index();
            $table->boolean('locked_by_admin')->default(false);
            $table->timestamp('last_failed_login_at')->nullable();
            $table->string('last_failed_login_ip', 45)->nullable();
            $table->timestamp('last_successful_login_at')->nullable();
            $table->string('last_successful_login_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index('tenant_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user_security_states');
    }
};
