<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminOrder extends Model
{
    use HasFactory;

    protected $table = 'admin_order';

    protected $fillable = [
        'sum_product',
        'order_time',
        'address'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity');
    }

    public static function getTodayOrders()
    {
        $sql = self::where('created_at', '>=', Carbon::today());
        return $sql->get();
    }
}
