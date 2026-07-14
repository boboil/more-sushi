<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class Category
 *
 * @property int $id
 * @property string $title
 * @property string|null $slug
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Collection|Product[] $products The products associated with the category.
 *
 * @package App\Models\Shop
 */
class Category extends Model
{
    use HasFactory;

    protected $table = 'category';

    protected $fillable = [
        'title', 'slug', 'description', 'meta_title', 'meta_description', 'image', 'enable',
    ];

    protected $casts = ['enable' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            $category->slug = Str::slug($category->title);
        });
    }

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
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product_shop');
    }
}
