<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantProvisioningRun extends Model
{
    protected $fillable = [
        'tenant_id',
        'tenant_code',
        'schema_name',
        'operation',
        'status',
        'started_at',
        'finished_at',
        'error_message',
        'error_class',
        'request_id',
        'trace_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
