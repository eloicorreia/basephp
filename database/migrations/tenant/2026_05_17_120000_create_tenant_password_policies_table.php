<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_password_policies', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('min_length')->default(12);
            $table->unsignedSmallInteger('max_length')->default(100);
            $table->boolean('require_uppercase')->default(true);
            $table->boolean('require_lowercase')->default(true);
            $table->boolean('require_numbers')->default(true);
            $table->boolean('require_symbols')->default(true);
            $table->boolean('disallow_common_passwords')->default(true);
            $table->boolean('disallow_user_personal_data')->default(true);
            $table->unsignedSmallInteger('password_expiration_days')->nullable();
            $table->unsignedSmallInteger('password_history_count')->default(5);
            $table->unsignedSmallInteger('max_failed_attempts')->default(5);
            $table->unsignedSmallInteger('lockout_minutes')->default(15);
            $table->boolean('must_change_password_on_first_login')->default(true);
            $table->unsignedSmallInteger('temporary_password_expiration_minutes')->default(1440);
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('active', 'tenant_password_policies_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_password_policies');
    }
};
