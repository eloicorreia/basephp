<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantIntegrationSetting extends Model
{
    protected $fillable = [
        'integration_enabled', 'default_timeout_seconds', 'retry_attempts',
        'retry_backoff_seconds', 'circuit_breaker_enabled',
        'circuit_breaker_failure_threshold', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'integration_enabled' => 'boolean',
        'default_timeout_seconds' => 'integer',
        'retry_attempts' => 'integer',
        'retry_backoff_seconds' => 'integer',
        'circuit_breaker_enabled' => 'boolean',
        'circuit_breaker_failure_threshold' => 'integer',
    ];
}
