<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class QueueWorkerLog extends Model
{
    protected $table = 'queue_worker_logs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}