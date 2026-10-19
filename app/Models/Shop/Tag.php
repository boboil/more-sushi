<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Class Tag — фільтр товарів у меню (Гостре, Лосось…)
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property int $sort_order
 * @property bool $enable
 *
 * @package App\Models\Shop
 */
class Tag extends Model
{
    use HasFactory;

    protected $table = 'product_tags';

    protected $fillable = [
        'title', 'slug', 'sort_order', 'enable',
    ];

    protected $casts = ['enable' => 'boolean'];

    protected static function booted(): void
    {
        // slug іде в адресу фільтра (?f=salmon), тому генеруємо його лише якщо не задано вручну
        static::saving(function (Tag $tag): void {
            $tag->slug = Str::slug($tag->slug ?: $tag->title);
        });
    }

    public static function getEnabledTags()
    {
        return self::where('enable', true)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_shop_tag', 'tag_id', 'product_id');
    }
}
