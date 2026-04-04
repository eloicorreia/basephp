<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class MailConfig extends Model
{
    protected $table = 'mail_configs';

    protected $fillable = [
        'name',
        'driver',
        'host',
        'port',
        'encryption',
        'username',
        'password_encrypted',
        'from_address',
        'from_name',
        'reply_to_address',
        'reply_to_name',
        'timeout_seconds',
        'verify_peer',
        'verify_peer_name',
        'allow_self_signed',
        'is_active',
        'is_default',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'verify_peer' => 'boolean',
        'verify_peer_name' => 'boolean',
        'allow_self_signed' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];
}