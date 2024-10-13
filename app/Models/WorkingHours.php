<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Role
 *
 * @property int $id
 * @property float $hours
 * @property int $user_id
 * @property Carbon|null $working_day
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User $user The user associated with the working hours.
 *
 */

class WorkingHours extends Model
{
    use HasFactory;

    protected $table = 'working_hours';
    protected $fillable = [
        'working_day',
        'user_id',
        'hours',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
