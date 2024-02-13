<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Product
 *
 * @property string $title
 * @property float $price
 * @property string $main_image
 * @property array $images
 * @property string $description
 * @property int $sort_order
 */
class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $fillable = [
        'title',
        'price',
        'main_image',
        'images',
        'description',
        'sort_order'
    ];

    public function orders()
    {
        return $this->belongsToMany(AdminOrder::class);
    }

    /**
     * @return Collection
     */
    public static function getProducts()
    {
        return self::orderBy('sort_order')->get();
    }
}
