<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HeroSlide
 *
 * @property int $id
 * @property string $image
 * @property string $button_text
 * @property string $button_link
 * @property int $sort_order
 * @property bool $enable
 *
 * @package App\Models\Shop
 */
class HeroSlide extends Model
{
    use HasFactory;

    protected $table = 'hero_slides';

    protected $fillable = [
        'image', 'button_text', 'button_link', 'sort_order', 'enable',
    ];

    protected $casts = ['enable' => 'boolean'];

    public static function getEnabledSlides()
    {
        return self::where('enable', true)->orderBy('sort_order')->get();
    }
}
