<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
