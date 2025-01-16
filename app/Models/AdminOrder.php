<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * Class AdminOrder
 *
 * @property integer $sum_product
 * @property integer $sum_product_without_sets
 * @property Carbon $order_time
 * @property string $address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Product[] $products
 */
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
    public static function getYesterdayOrders()
    {
        $sql = self::where('created_at', '>=', Carbon::yesterday())->where('created_at', '<', Carbon::today());
        return $sql->get();
    }
}
