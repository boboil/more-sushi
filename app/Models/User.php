<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property float $rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|WorkingHours[] $workingDays The user associated with the working hours.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * @return bool
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }
    public function setPasswordAttribute($password): void
    {
        $this->attributes['password'] = bcrypt($password);
    }
    public function me($item): bool
    {
        return $this->id == $item->user_id;
    }
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
    public static function getAllWorkers()
    {
        return User::whereDoesntHave('roles', function ($query) {
            $query->where('name', 'admin');
        });
    }
    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHours::class);
    }
}
