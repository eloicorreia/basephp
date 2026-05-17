<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantNotificationSetting extends Model
{
    protected $fillable = [
        'notify_admin_on_failed_jobs', 'notify_admin_on_email_failure',
        'notify_admin_on_permission_change',
        'notify_admin_on_integration_failure',
        'admin_notification_emails', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'notify_admin_on_failed_jobs' => 'boolean',
        'notify_admin_on_email_failure' => 'boolean',
        'notify_admin_on_permission_change' => 'boolean',
        'notify_admin_on_integration_failure' => 'boolean',
        'admin_notification_emails' => 'array',
    ];
}
