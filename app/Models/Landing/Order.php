<?php

namespace App\Models\Landing;

use App\Models\Shop\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    protected $table = 'l_orders';

    protected $fillable = ['name', 'phone', 'address', 'time', 'sum', 'self_pickup'];

    protected $casts = [
        'time' => 'datetime',
        'self_pickup' => 'boolean',
    ];

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
