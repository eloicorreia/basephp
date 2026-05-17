<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_audit_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->boolean('audit_enabled')->default(true);
            $table->boolean('audit_store_before_after')->default(true);
            $table->boolean('audit_payload_enabled')->default(false);
            $table->boolean('audit_sensitive_payload_masking')->default(true);
            $table->unsignedSmallInteger('api_request_log_retention_days')->default(90);
            $table->unsignedSmallInteger('audit_log_retention_days')->default(365);
            $table->unsignedSmallInteger('integration_log_retention_days')->default(180);
            $table->unsignedSmallInteger('email_log_retention_days')->default(180);
            $table->unsignedSmallInteger('queue_log_retention_days')->default(90);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_audit_settings');
    }
};
