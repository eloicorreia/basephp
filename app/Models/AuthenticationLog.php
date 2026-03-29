<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthenticationLog extends Model
{
    public $timestamps = false;

    protected $table = 'authentication_logs';

    protected $fillable = [
        'request_id',
        'trace_id',
        'user_id',
        'username',
        'tenant_code',
        'oauth_client_id',
        'event_type',
        'processing_status',
        'ip',
        'user_agent',
        'message',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];
}