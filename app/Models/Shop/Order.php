<?php

namespace App\Models\Shop;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $table = 'shop_order';

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'shop_order_shop_product',
            'shop_order_id',
            'shop_product_id'
        )->withPivot('shop_product_quantity');
    }

    public static function getTodayOrders()
    {
        return self::whereDate('created_at', DB::raw('CURDATE()'))->get();
    }
}
