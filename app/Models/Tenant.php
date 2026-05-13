<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ERROR = 'error';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PROVISIONING = 'provisioning';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'schema_name',
        'status',
    ];

    /**
     * @return HasMany<TenantUser, $this>
     */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }
}
