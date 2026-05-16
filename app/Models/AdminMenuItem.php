<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminMenuPermissionStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminMenuItem extends Model
{
    protected $fillable = [
        'admin_menu_group_id',
        'parent_id',
        'code',
        'title',
        'translation_key',
        'route_name',
        'active_route_pattern',
        'icon',
        'order',
        'active',
        'opens_in_new_tab',
        'permission_strategy',
    ];

    protected $casts = [
        'order' => 'integer',
        'active' => 'boolean',
        'opens_in_new_tab' => 'boolean',
        'permission_strategy' => AdminMenuPermissionStrategy::class,
    ];

    /**
     * @return BelongsTo<AdminMenuGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AdminMenuGroup::class, 'admin_menu_group_id');
    }

    /**
     * @return BelongsTo<AdminMenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(AdminMenuItem::class, 'parent_id');
    }

    /**
     * @return HasMany<AdminMenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(AdminMenuItem::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'admin_menu_item_permissions')
            ->withTimestamps();
    }
}
