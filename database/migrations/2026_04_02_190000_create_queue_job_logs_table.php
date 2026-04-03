<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_job_logs', function (Blueprint $table): void {
            $table->id();

            $table->uuid('event_uuid')->unique();

            $table->string('category', 50)->default('queue');
            $table->string('event_type', 50);
            $table->string('operation', 100);
            $table->string('status', 30);

            $table->string('job_uuid', 100)->nullable()->index();
            $table->string('batch_id', 100)->nullable()->index();
            $table->string('job_class', 255)->index();

            $table->string('queue_connection', 50)->index();
            $table->string('queue_name', 100)->index();

            $table->unsignedInteger('attempt')->nullable();
            $table->unsignedInteger('max_tries')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();

            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('tenant_code', 100)->nullable()->index();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('oauth_client_id')->nullable()->index();

            $table->string('request_id', 100)->nullable()->index();
            $table->string('trace_id', 100)->nullable()->index();

            $table->string('external_reference', 150)->nullable()->index();

            $table->text('message')->nullable();
            $table->string('exception_class', 255)->nullable();
            $table->text('exception_message')->nullable();

            $table->jsonb('input_payload')->nullable();
            $table->jsonb('output_payload')->nullable();
            $table->jsonb('context')->nullable();

            $table->timestampTz('processed_at')->nullable()->index();
            $table->timestampsTz();

            $table->index(
                ['status', 'event_type', 'processed_at'],
                'queue_job_logs_status_event_processed_idx'
            );

            $table->index(
                ['tenant_id', 'queue_name', 'processed_at'],
                'queue_job_logs_tenant_queue_processed_idx'
            );

            $table->index(
                ['request_id', 'trace_id'],
                'queue_job_logs_request_trace_idx'
            );
        });

        DB::statement(<<<'SQL'
            ALTER TABLE queue_job_logs
            ADD CONSTRAINT queue_job_logs_event_type_check
            CHECK (
                event_type IN (
                    'dispatched',
                    'started',
                    'succeeded',
                    'failed',
                    'released',
                    'retried',
                    'skipped',
                    'batch_started',
                    'batch_finished',
                    'batch_cancelled'
                )
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE queue_job_logs
            ADD CONSTRAINT queue_job_logs_status_check
            CHECK (
                status IN (
                    'queued',
                    'processing',
                    'success',
                    'failed',
                    'released',
                    'skipped',
                    'cancelled'
                )
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_job_logs');
    }
};