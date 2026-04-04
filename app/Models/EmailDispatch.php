<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class EmailDispatch extends Model
{
    protected $table = 'email_dispatches';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'to_recipients' => 'array',
            'cc_recipients' => 'array',
            'bcc_recipients' => 'array',
            'is_html' => 'boolean',
            'attempts' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}