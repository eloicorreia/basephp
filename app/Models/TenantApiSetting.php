<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantApiSetting extends Model
{
    protected $fillable = [
        'api_enabled', 'api_rate_limit_per_minute',
        'strict_rate_limit_per_minute', 'login_rate_limit_per_minute',
        'api_default_pagination_size', 'api_max_pagination_size',
        'api_require_correlation_id', 'api_allowed_origins',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'api_enabled' => 'boolean',
        'api_rate_limit_per_minute' => 'integer',
        'strict_rate_limit_per_minute' => 'integer',
        'login_rate_limit_per_minute' => 'integer',
        'api_default_pagination_size' => 'integer',
        'api_max_pagination_size' => 'integer',
        'api_require_correlation_id' => 'boolean',
        'api_allowed_origins' => 'array',
    ];
}
