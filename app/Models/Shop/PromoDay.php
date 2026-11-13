<?php

namespace App\Models\Shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Акція одного дня тижня для лендингу /promo/<день>.
 *
 * @property int $weekday 1 = понеділок … 7 = неділя
 * @property bool $is_active
 * @property string $offer_type percent | gift
 * @property int|null $discount_percent
 * @property string $offer_title
 * @property string $offer_short
 * @property string|null $gift_badge
 * @property string $active_from HH:MM
 * @property string $active_to HH:MM
 * @property string|null $terms
 * @property string|null $hero_image
 */
class PromoDay extends Model
{
    public const TYPE_PERCENT = 'percent';

    public const TYPE_GIFT = 'gift';

    /** Порядок як у ISO: 1 = понеділок. Ключі збігаються з фронтом (shared/types/promo.ts). */
    public const DAYS = [
        1 => ['key' => 'mon', 'slug' => 'ponedilok', 'label' => 'Понеділок', 'short' => 'ПН'],
        2 => ['key' => 'tue', 'slug' => 'vivtorok', 'label' => 'Вівторок', 'short' => 'ВТ'],
        3 => ['key' => 'wed', 'slug' => 'sereda', 'label' => 'Середа', 'short' => 'СР'],
        4 => ['key' => 'thu', 'slug' => 'chetver', 'label' => 'Четвер', 'short' => 'ЧТ'],
        5 => ['key' => 'fri', 'slug' => 'piatnytsia', 'label' => "П'ятниця", 'short' => 'ПТ'],
        6 => ['key' => 'sat', 'slug' => 'subota', 'label' => 'Субота', 'short' => 'СБ'],
        7 => ['key' => 'sun', 'slug' => 'nedilia', 'label' => 'Неділя', 'short' => 'НД'],
    ];

    protected $table = 'promo_days';

    protected $fillable = [
        'weekday', 'is_active', 'offer_type', 'discount_percent', 'offer_title', 'offer_short',
        'gift_badge', 'active_from', 'active_to', 'terms', 'hero_image',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'is_active' => 'boolean',
        'discount_percent' => 'integer',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promo_day_product', 'promo_day_id', 'product_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function label(): string
    {
        return self::DAYS[$this->weekday]['label'] ?? (string) $this->weekday;
    }

    public function isPercent(): bool
    {
        return $this->offer_type === self::TYPE_PERCENT && $this->discount_percent > 0;
    }

    /** Ціна товару зі знижкою дня (ціле число гривень, як і решта цін сайту). */
    public function discountedPrice(float $price): float
    {
        return $this->isPercent() ? (float) round($price * (100 - $this->discount_percent) / 100) : $price;
    }
}
