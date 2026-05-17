<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantAuditSetting extends Model
{
    protected $fillable = [
        'audit_enabled', 'audit_store_before_after', 'audit_payload_enabled',
        'audit_sensitive_payload_masking', 'api_request_log_retention_days',
        'audit_log_retention_days', 'integration_log_retention_days',
        'email_log_retention_days', 'queue_log_retention_days',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'audit_enabled' => 'boolean',
        'audit_store_before_after' => 'boolean',
        'audit_payload_enabled' => 'boolean',
        'audit_sensitive_payload_masking' => 'boolean',
        'api_request_log_retention_days' => 'integer',
        'audit_log_retention_days' => 'integer',
        'integration_log_retention_days' => 'integer',
        'email_log_retention_days' => 'integer',
        'queue_log_retention_days' => 'integer',
    ];
}
