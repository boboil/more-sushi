<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Каса за день і відсоток від неї, що йде у фонд зарплати.
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property string $cash
 * @property string $pool_percent
 * @property int|null $entered_by
 */
class SalaryDay extends Model
{
    protected $table = 'salary_days';

    protected $fillable = ['date', 'cash', 'pool_percent', 'entered_by', 'synced_at'];

    protected $casts = [
        'date' => 'date',
        'cash' => 'decimal:2',
        'pool_percent' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    public function pool(): float
    {
        return round((float) $this->cash * (float) $this->pool_percent / 100, 2);
    }
}
