<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure\Queue;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class QueueInfrastructureTest extends TestCase
{
    public function test_queue_default_connection_must_be_database(): void
    {
        $this->assertSame('database', Config::get('queue.default'));
    }

    public function test_jobs_table_must_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('jobs'),
            'The jobs table must exist.'
        );
    }

    public function test_job_batches_table_must_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('job_batches'),
            'The job_batches table must exist.'
        );
    }

    public function test_failed_jobs_table_must_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable('failed_jobs'),
            'The failed_jobs table must exist.'
        );
    }

    public function test_jobs_table_must_have_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('jobs', [
            'id',
            'queue',
            'payload',
            'attempts',
            'reserved_at',
            'available_at',
            'created_at',
        ]));
    }

    public function test_job_batches_table_must_have_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('job_batches', [
            'id',
            'name',
            'total_jobs',
            'pending_jobs',
            'failed_jobs',
            'failed_job_ids',
            'options',
            'cancelled_at',
            'created_at',
            'finished_at',
        ]));
    }

    public function test_failed_jobs_table_must_have_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('failed_jobs', [
            'id',
            'uuid',
            'connection',
            'queue',
            'payload',
            'exception',
            'failed_at',
        ]));
    }
}