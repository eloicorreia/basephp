<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantWebhookSetting extends Model
{
    protected $fillable = [
        'webhook_enabled', 'webhook_url', 'webhook_secret_encrypted',
        'webhook_events', 'webhook_retry_attempts', 'webhook_timeout_seconds',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'webhook_enabled' => 'boolean',
        'webhook_events' => 'array',
        'webhook_retry_attempts' => 'integer',
        'webhook_timeout_seconds' => 'integer',
    ];
}
