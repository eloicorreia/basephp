<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantPasswordPolicy extends Model
{
    protected $fillable = [
        'min_length',
        'max_length',
        'require_uppercase',
        'require_lowercase',
        'require_numbers',
        'require_symbols',
        'disallow_common_passwords',
        'disallow_user_personal_data',
        'password_expiration_days',
        'password_history_count',
        'max_failed_attempts',
        'lockout_minutes',
        'must_change_password_on_first_login',
        'temporary_password_expiration_minutes',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_length' => 'integer',
        'max_length' => 'integer',
        'require_uppercase' => 'boolean',
        'require_lowercase' => 'boolean',
        'require_numbers' => 'boolean',
        'require_symbols' => 'boolean',
        'disallow_common_passwords' => 'boolean',
        'disallow_user_personal_data' => 'boolean',
        'password_expiration_days' => 'integer',
        'password_history_count' => 'integer',
        'max_failed_attempts' => 'integer',
        'lockout_minutes' => 'integer',
        'must_change_password_on_first_login' => 'boolean',
        'temporary_password_expiration_minutes' => 'integer',
        'active' => 'boolean',
    ];
}
