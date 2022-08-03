<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product_shop';

    public static function getProductsBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }
    public function category()
    {
        return $this->belongsToMany(Category::class, 'category_product_shop');
    }
}
