<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantSecuritySetting extends Model
{
    protected $fillable = [
        'session_lifetime_minutes', 'idle_timeout_minutes',
        'force_single_session_per_user', 'logout_on_password_change',
        'max_login_attempts', 'lockout_duration_minutes',
        'unlock_requires_admin', 'notify_user_on_failed_login',
        'notify_admin_on_lockout', 'allowed_ip_ranges',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'session_lifetime_minutes' => 'integer',
        'idle_timeout_minutes' => 'integer',
        'force_single_session_per_user' => 'boolean',
        'logout_on_password_change' => 'boolean',
        'max_login_attempts' => 'integer',
        'lockout_duration_minutes' => 'integer',
        'unlock_requires_admin' => 'boolean',
        'notify_user_on_failed_login' => 'boolean',
        'notify_admin_on_lockout' => 'boolean',
        'allowed_ip_ranges' => 'array',
    ];
}
