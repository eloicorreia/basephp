<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    public $timestamps = false;

    protected $table = 'integration_logs';

    protected $fillable = [
        'request_id',
        'trace_id',
        'system_name',
        'direction',
        'operation',
        'endpoint',
        'external_identifier',
        'request_payload',
        'response_payload',
        'http_status',
        'processing_status',
        'message',
        'created_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'created_at' => 'datetime',
    ];
}