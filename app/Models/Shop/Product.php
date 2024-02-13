<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


/**
 * Class Product
 *
 * @property string $title
 * @property string $description
 * @property float $price
 * @property float $discount
 * @property string $main_image
 * @property array $images
 * @property int $sort_order
 * @property string $slug
 * @property string $consist
 * @property boolean $for_landing
 * @property boolean $stock
 * @property int $count
 * @property int $weight
 * @property boolean $isRelated
 * @property boolean $latest
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection|Category $category The category associated with the product.
 *
 */
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

    public static function getProductsForLanding()
    {
        return self::where('for_landing', true)->with('category')->get();
    }
}
