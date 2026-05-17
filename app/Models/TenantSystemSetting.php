<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantSystemSetting extends Model
{
    protected $fillable = [
        'timezone', 'locale', 'date_format', 'datetime_format',
        'default_items_per_page', 'max_items_per_page', 'support_email',
        'support_phone', 'maintenance_mode', 'maintenance_message',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'default_items_per_page' => 'integer',
        'max_items_per_page' => 'integer',
        'maintenance_mode' => 'boolean',
    ];
}
