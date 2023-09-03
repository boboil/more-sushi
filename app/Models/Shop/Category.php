<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category';

    public static function getEnableCategories()
    {
        return self::where('enable', 1)->get();
    }
    public static function getStockCategories()
    {
        return self::with(['products'=> function($q) {
            $q->where('stock', true);
            $q->orWhere('latest', true);
        }])->whereHas('products', function (Builder $query) {
            $query->where('stock', true);
            $query->orWhere('latest', true);
        })->get();
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product_shop');
    }
}
