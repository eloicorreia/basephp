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
        Schema::create('queue_worker_logs', function (Blueprint $table): void {
            $table->id();

            $table->uuid('event_uuid')->unique();

            $table->string('category', 50)->default('queue_worker');
            $table->string('event_type', 50);
            $table->string('operation', 100);
            $table->string('status', 30);

            $table->string('worker_name', 100)->nullable()->index();
            $table->string('queue_connection', 50)->index();
            $table->string('queue_names', 255)->nullable();

            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('tenant_code', 100)->nullable()->index();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('oauth_client_id')->nullable()->index();

            $table->string('request_id', 100)->nullable()->index();
            $table->string('trace_id', 100)->nullable()->index();

            $table->unsignedBigInteger('pid')->nullable()->index();
            $table->string('host', 150)->nullable()->index();

            $table->unsignedBigInteger('duration_ms')->nullable();

            $table->text('message')->nullable();
            $table->string('exception_class', 255)->nullable();
            $table->text('exception_message')->nullable();

            $table->jsonb('context')->nullable();

            $table->timestampTz('processed_at')->nullable()->index();
            $table->timestampsTz();

            $table->index(
                ['status', 'event_type', 'processed_at'],
                'queue_worker_logs_status_event_processed_idx'
            );

            $table->index(
                ['request_id', 'trace_id'],
                'queue_worker_logs_request_trace_idx'
            );
        });

        DB::statement(<<<'SQL'
            ALTER TABLE queue_worker_logs
            ADD CONSTRAINT queue_worker_logs_event_type_check
            CHECK (
                event_type IN (
                    'worker_started',
                    'worker_stopped',
                    'worker_restarted',
                    'worker_failed',
                    'worker_idle',
                    'worker_timeout'
                )
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE queue_worker_logs
            ADD CONSTRAINT queue_worker_logs_status_check
            CHECK (
                status IN (
                    'running',
                    'stopped',
                    'failed',
                    'idle',
                    'timeout'
                )
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_worker_logs');
    }
};