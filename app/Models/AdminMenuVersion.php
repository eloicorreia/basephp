<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminMenuVersion extends Model
{
    protected $fillable = [
        'version',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'changed_at' => 'datetime',
    ];
}
