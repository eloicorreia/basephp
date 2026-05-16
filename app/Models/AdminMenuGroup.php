<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminMenuGroup extends Model
{
    protected $fillable = [
        'code',
        'title',
        'translation_key',
        'icon',
        'order',
        'active',
    ];

    protected $casts = [
        'order' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * @return HasMany<AdminMenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AdminMenuItem::class);
    }
}
