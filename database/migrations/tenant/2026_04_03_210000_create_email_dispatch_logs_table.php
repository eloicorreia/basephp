<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('email_dispatch_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('request_id', 100)->nullable();
            $table->string('trace_id', 100)->nullable();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_code', 100)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('trigger', 150);
            $table->string('status', 30);
            $table->unsignedInteger('attempt_count')->default(0);

            $table->unsignedBigInteger('mail_config_id')->nullable();
            $table->string('mail_config_name', 150)->nullable();

            $table->string('driver', 20)->nullable();
            $table->string('host', 255)->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('encryption', 20)->nullable();
            $table->string('from_address', 255)->nullable();
            $table->string('from_name', 255)->nullable();

            $table->jsonb('to_recipients');
            $table->jsonb('cc_recipients')->nullable();
            $table->jsonb('bcc_recipients')->nullable();

            $table->string('subject', 255);
            $table->text('html_body')->nullable();
            $table->text('text_body')->nullable();

            $table->string('queue_connection', 100)->nullable();
            $table->string('queue_name', 100)->nullable();
            $table->string('job_uuid', 100)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->string('idempotency_key', 150)->nullable();

            $table->string('error_class', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->text('stack_trace_summary')->nullable();

            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sending_started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->jsonb('context')->nullable();
            $table->timestamps();

            $table->index(['tenant_code', 'status'], 'email_dispatch_logs_tenant_status_idx');
            $table->index('request_id', 'email_dispatch_logs_request_id_idx');
            $table->index('trace_id', 'email_dispatch_logs_trace_id_idx');
            $table->index('job_uuid', 'email_dispatch_logs_job_uuid_idx');
            $table->unique('idempotency_key', 'email_dispatch_logs_idempotency_key_uidx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_dispatch_logs');
    }
};