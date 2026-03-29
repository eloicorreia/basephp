<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'schema_name',
        'status',
    ];

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }
}