<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'trace_id',
        'level',
        'category',
        'service',
        'operation',
        'route',
        'method',
        'user_id',
        'ip',
        'message',
        'context',
        'input_payload',
        'output_payload',
        'http_status',
        'processing_status',
        'stack_trace_summary',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'input_payload' => 'array',
        'output_payload' => 'array',
        'created_at' => 'datetime',
    ];
}