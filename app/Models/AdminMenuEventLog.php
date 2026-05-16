<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMenuEventLog extends Model
{
    protected $fillable = [
        'occurred_at',
        'event',
        'level',
        'entity_type',
        'entity_id',
        'user_id',
        'request_id',
        'trace_id',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'context',
        'message',
        'stack_summary',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'old_values' => 'array',
        'new_values' => 'array',
        'context' => 'array',
    ];
}
