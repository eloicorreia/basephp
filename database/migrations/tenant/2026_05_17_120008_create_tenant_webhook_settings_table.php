<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_webhook_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->boolean('webhook_enabled')->default(false);
            $table->string('webhook_url', 500)->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->jsonb('webhook_events')->nullable();
            $table->unsignedSmallInteger('webhook_retry_attempts')->default(3);
            $table->unsignedSmallInteger('webhook_timeout_seconds')->default(15);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_webhook_settings');
    }
};
