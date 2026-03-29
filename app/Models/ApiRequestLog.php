<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    public $timestamps = false;

    protected $table = 'api_request_logs';

    protected $guarded = [];

    protected $casts = [
        'request_headers' => 'array',
        'request_query' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
        'created_at' => 'datetime',
    ];
}