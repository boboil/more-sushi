<?php

namespace App\Models\Landing;

use App\Models\Shop\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    protected $table = 'l_orders';

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'l_order_shop_product',
            'l_order_id',
            'shop_product_id'
        )->withPivot('shop_product_quantity');
    }
}
