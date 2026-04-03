<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Queue;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class QueueLoggingTablesTest extends TestCase
{
    public function test_queue_job_logs_table_must_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('queue_job_logs'),
            'The queue_job_logs table must exist.'
        );
    }

    public function test_queue_worker_logs_table_must_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('queue_worker_logs'),
            'The queue_worker_logs table must exist.'
        );
    }

    public function test_queue_job_logs_table_must_have_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('queue_job_logs', [
            'id',
            'event_uuid',
            'category',
            'event_type',
            'operation',
            'status',
            'job_class',
            'queue_connection',
            'queue_name',
            'tenant_id',
            'tenant_code',
            'request_id',
            'trace_id',
            'message',
            'input_payload',
            'output_payload',
            'context',
            'processed_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_queue_worker_logs_table_must_have_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('queue_worker_logs', [
            'id',
            'event_uuid',
            'category',
            'event_type',
            'operation',
            'status',
            'worker_name',
            'queue_connection',
            'queue_names',
            'tenant_id',
            'tenant_code',
            'request_id',
            'trace_id',
            'pid',
            'host',
            'message',
            'context',
            'processed_at',
            'created_at',
            'updated_at',
        ]));
    }
}