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
 * @property float $rate
 * @property Carbon $month
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Role $role The role associated with the rate.
 *
 */
class Rate extends Model
{
    use HasFactory;

    protected $table = 'rates';


    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
