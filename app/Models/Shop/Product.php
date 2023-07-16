<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product_shop';

    public static function getProductsBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }
    public static function getProductsByTitle($title)
    {
        return self::where('title', $title)->first();
    }
    public static function getProductsByPosterId($poster_id)
    {
        return self::where('poster_id', $poster_id)->first();
    }
    public function category()
    {
        return $this->belongsToMany(Category::class, 'category_product_shop');
    }

    public static function getRelatedProducts() {
        return self::where('isRelated', true)->get();
    }

    public function addProduct($product, $data) {
        $product->title = $data['product_name'];
        $product->price = $this->transformPrice($data['price']['4']);
        $product->count = 999;
        $product->weight = $data['out'];
        $product->stock = 1;
        $product->latest = 0;
        $product->main_image = $data['photo'];
        $product->description = $data['product_production_description'];
        $product->title = Str::slug($data['product_name']);
        $product->isRelated = 0;
        $product->save();
        return $product;
    }
    public function transformPrice ($price) {
        return (int)$price / 100;
    }
}
