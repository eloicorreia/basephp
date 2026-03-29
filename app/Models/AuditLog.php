<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'audit_logs';

    protected $fillable = [
        'request_id',
        'trace_id',
        'user_id',
        'user_role',
        'action',
        'auditable_type',
        'auditable_id',
        'before_data',
        'after_data',
        'route',
        'method',
        'ip',
        'created_at',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'created_at' => 'datetime',
    ];
}