<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class BusinessLog extends Model
{
    public $timestamps = false;

    protected $table = 'business_logs';

    protected $fillable = [
        'request_id',
        'trace_id',
        'user_id',
        'category',
        'operation',
        'entity_type',
        'entity_id',
        'message',
        'context',
        'processing_status',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];
}