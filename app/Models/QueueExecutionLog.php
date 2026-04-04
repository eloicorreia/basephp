<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class QueueExecutionLog extends Model
{
    protected $table = 'queue_execution_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'occurred_at' => 'datetime',
            'attempt' => 'integer',
            'job_id' => 'integer',
        ];
    }
}