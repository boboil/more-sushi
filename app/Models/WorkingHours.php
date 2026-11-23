<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Відпрацьовані години працівника за день (один запис на день).
 *
 * @property int $id
 * @property float $hours
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $working_day
 * @property string $source self | cashier | admin | poster
 * @property int|null $entered_by
 *
 * @property User $user
 */
class WorkingHours extends Model
{
    use HasFactory;

    public const SOURCE_SELF = 'self';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_POSTER = 'poster';
    public const SOURCE_CASHIER = 'cashier';

    protected $table = 'working_hours';

    protected $fillable = [
        'working_day',
        'user_id',
        'hours',
        'source',
        'entered_by',
    ];

    protected $casts = [
        'working_day' => 'date',
        'hours' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
