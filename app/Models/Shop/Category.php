<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category';

    public static function getEnableCategories()
    {
        return self::where('enable', 1)->get();
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product_shop');
    }
}
