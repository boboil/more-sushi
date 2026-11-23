<?php

namespace App\Services\Promo;

/**
 * Результат «Акції дня» для кошика: що отримує клієнт і що передати в Poster.
 */
class PromoQuote
{
    /**
     * @param  int  $discount  уся знижка акції, грн
     * @param  int  $extraDiscount  частина знижки, якої ще немає в цінах меню (безкоштовні товари з кошика)
     * @param  list<array{posterId: int, productId: int|null, name: string, count: int, price: float, items: list<array{productId: int, name: string, price: float, image: string|null}>}>  $gifts  подарунки поза кошиком; items — товари сайту, якими його показати
     * @param  list<array{posterId: int, productId: int|null, name: string, price: float, items: list<array>}>  $giftOptions  з чого обирати подарунок (якщо варіантів кілька)
     * @param  array<int, int>  $freeItems  товар сайту => скільки штук із кошика безкоштовні
     * @param  array<int, int>  $involved  товар Poster => кількість, що бере участь в акції
     * @param  array<int, int>  $result  товар Poster => кількість, яку каса додасть як результат акції
     */
    public function __construct(
        public readonly int $weekday,
        public readonly int $posterPromotionId,
        public readonly bool $applied,
        public readonly int $discount = 0,
        public readonly int $extraDiscount = 0,
        public readonly array $gifts = [],
        public readonly array $giftOptions = [],
        public readonly ?int $selectedGift = null,
        public readonly array $freeItems = [],
        public readonly ?string $hint = null,
        public readonly array $involved = [],
        public readonly array $result = [],
    ) {}

    /** Параметр promotion для incomingOrders.createIncomingOrder. */
    public function posterPromotion(): array
    {
        if (! $this->applied) {
            return [];
        }
        $promotion = [
            'id' => $this->posterPromotionId,
            'involved_products' => self::products($this->involved),
        ];
        if ($this->result) {
            $promotion['result_products'] = self::products($this->result);
        }

        return [$promotion];
    }

    public function toArray(): array
    {
        return [
            'active' => true,
            'weekday' => $this->weekday,
            'posterPromotionId' => $this->posterPromotionId,
            'applied' => $this->applied,
            'discount' => $this->discount,
            'extraDiscount' => $this->extraDiscount,
            'gifts' => $this->gifts,
            'giftOptions' => $this->giftOptions,
            'selectedGift' => $this->selectedGift,
            'freeItems' => collect($this->freeItems)->map(fn (int $count, int $id) => ['productId' => $id, 'count' => $count])->values()->all(),
            'hint' => $this->hint,
        ];
    }

    private static function products(array $counts): array
    {
        return collect($counts)->map(fn (int $count, int $id) => ['id' => $id, 'count' => $count])->values()->all();
    }
}
