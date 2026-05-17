<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_notification_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->boolean('notify_admin_on_failed_jobs')->default(true);
            $table->boolean('notify_admin_on_email_failure')->default(true);
            $table->boolean('notify_admin_on_permission_change')->default(true);
            $table->boolean('notify_admin_on_integration_failure')->default(true);
            $table->jsonb('admin_notification_emails')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_notification_settings');
    }
};
