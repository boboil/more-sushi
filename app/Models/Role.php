<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Role
 *
 * @property int $id
 * @property string $name
 * @property string|null $label
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Rate $rate The rate associated with the role.
 */
class Role extends Model
{
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Grant the given permission to a role.
     *
     * @param  Permission $permission
     * @return mixed
     */
    public function givePermissionTo(Permission $permission): mixed
    {
        return $this->permissions()->save($permission);
    }

    /**
     * A user may have multiple roles.
     *
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
