<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantQueueSetting extends Model
{
    protected $fillable = [
        'default_queue', 'email_queue', 'max_job_attempts',
        'job_retry_delay_seconds', 'failed_job_notify_admin',
        'queue_processing_enabled', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'max_job_attempts' => 'integer',
        'job_retry_delay_seconds' => 'integer',
        'failed_job_notify_admin' => 'boolean',
        'queue_processing_enabled' => 'boolean',
    ];
}
