<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_integration_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->boolean('integration_enabled')->default(true);
            $table->unsignedSmallInteger('default_timeout_seconds')->default(30);
            $table->unsignedSmallInteger('retry_attempts')->default(3);
            $table->unsignedSmallInteger('retry_backoff_seconds')->default(5);
            $table->boolean('circuit_breaker_enabled')->default(false);
            $table->unsignedSmallInteger('circuit_breaker_failure_threshold')->default(5);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_integration_settings');
    }
};
