<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_queue_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('default_queue', 80)->default('default');
            $table->string('email_queue', 80)->default('notifications');
            $table->unsignedSmallInteger('max_job_attempts')->default(3);
            $table->unsignedInteger('job_retry_delay_seconds')->default(60);
            $table->boolean('failed_job_notify_admin')->default(true);
            $table->boolean('queue_processing_enabled')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_queue_settings');
    }
};
