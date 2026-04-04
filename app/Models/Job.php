<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Job extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'available_at' => 'integer',
            'reserved_at' => 'integer',
            'created_at' => 'integer',
            'attempts' => 'integer',
        ];
    }
}