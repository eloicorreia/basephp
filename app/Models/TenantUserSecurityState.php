<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenantUserSecurityState extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'failed_login_attempts',
        'locked_until',
        'locked_by_admin',
        'last_failed_login_at',
        'last_failed_login_ip',
        'last_successful_login_at',
        'last_successful_login_ip',
    ];

    protected $casts = [
        'failed_login_attempts' => 'integer',
        'locked_until' => 'datetime',
        'locked_by_admin' => 'boolean',
        'last_failed_login_at' => 'datetime',
        'last_successful_login_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
