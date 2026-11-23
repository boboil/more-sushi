<?php

namespace Tests\Feature;

use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Product;
use App\Models\Shop\PromoDay;
use App\Services\Promo\PromoDays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

/**
 * «Акція дня» за правилами акції Poster: кошик рахується як на касі, акція йде в онлайн-замовлення.
 * Акції — копії реальних (#91, #93, #94, #96) з Poster.
 */
class PosterPromoDayTest extends TestCase
{
    use RefreshDatabase;

    private const CAT_ROLLS = 5;

    private const CAT_SETS = 7;

    // понеділок 23.11.2026, 14:00 за Києвом; далі +1 день
    private const MONDAY = '2026-11-23 14:00:00';

    /** @var list<array> */
    private array $sent = [];

    /** @var list<int> на які спроби Poster відповідає помилкою (1 — перша) */
    private array $rejectAttempts = [];

    private array $posterProducts = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(self::MONDAY);
        app(PromoDays::class)->setEnabled(true);
        $this->fakePoster();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_migration_links_days_to_poster_promotions(): void
    {
        $this->assertSame(
            [1 => 91, 2 => 93, 3 => 94, 4 => 95, 5 => null, 6 => null, 7 => 96],
            PromoDay::query()->orderBy('weekday')->pluck('poster_promotion_id', 'weekday')->all(),
        );
    }

    public function test_monday_percent_on_whole_category_comes_from_poster(): void
    {
        $set = $this->product('Сет Дует', 1000, 501, self::CAT_SETS);
        $roll = $this->product('Філадельфія', 300, 601, self::CAT_ROLLS);

        $day = $this->getJson('/api/shop/promo-days')->assertOk()->json('days.0');
        $this->assertSame(91, $day['posterPromotionId']);
        $this->assertSame(15, $day['discountPercent'], 'відсоток із Poster, а не з адмінки (20)');
        $this->assertSame([$set->id], $day['discountProductIds']);

        $this->quote([$set->id => 2, $roll->id => 1])
            ->assertJsonPath('applied', true)
            ->assertJsonPath('discount', 300)
            ->assertJsonPath('extraDiscount', 0); // уже в цінах меню

        $this->postJson('/api/shop/add-order', $this->order([$set->id => 2, $roll->id => 1], 2000))->assertOk();
        $this->assertSame([['id' => 91, 'involved_products' => [['id' => 501, 'count' => 2]]]], $this->sent[0]['promotion']);
        $this->assertStringContainsString('АКЦІЯ ДНЯ (Понеділок, Poster #91): Знижка 300 грн', $this->sent[0]['comment']);
    }

    public function test_tuesday_gift_by_order_sum_with_customer_choice(): void
    {
        Carbon::setTestNow('2026-11-24 14:00:00');
        $trio = $this->product('Запечене Тріо', 655, 977, self::CAT_SETS);
        $roll = $this->product('Філадельфія', 300, 601, self::CAT_ROLLS);

        $this->quote([$roll->id => 2])
            ->assertJsonPath('applied', false)
            ->assertJsonPath('hint', 'Додайте страв ще на 100 грн — і Запечене Тріо або Дві піци у подарунок')
            ->assertJsonCount(2, 'giftOptions');

        $this->quote([$roll->id => 3], 1430)
            ->assertJsonPath('applied', true)
            ->assertJsonPath('selectedGift', 1430)
            ->assertJsonPath('gifts.0.name', 'Дві піци')
            ->assertJsonPath('gifts.0.count', 1);
        // за замовчуванням — перший варіант, і в нього є картка на сайті
        $this->quote([$roll->id => 3])->assertJsonPath('gifts.0.productId', $trio->id);

        $this->postJson('/api/shop/add-order', $this->order([$roll->id => 3], 900) + ['gift' => 1430])->assertOk();
        $this->assertSame([['product_id' => 601, 'count' => 3]], $this->sent[0]['products'], 'подарунок у товари не кладемо — його додає каса');
        $this->assertSame([[
            'id' => 93,
            'involved_products' => [['id' => 601, 'count' => 3]],
            'result_products' => [['id' => 1430, 'count' => 1]],
        ]], $this->sent[0]['promotion']);
    }

    public function test_gift_set_from_poster_is_shown_as_site_products(): void
    {
        Carbon::setTestNow('2026-11-24 14:00:00');
        $montana = $this->product('Піца Тоні Монтана ', 195, 1295, 99);
        $carbonara = $this->product('Піца Карбонара450г', 215, 285, 99);
        $roll = $this->product('Філадельфія', 300, 601, self::CAT_ROLLS);
        PromoDay::query()->where('weekday', 2)->update(['gift_display' => json_encode([['poster_id' => 1430, 'products' => [(string) $montana->id, (string) $carbonara->id]]])]);

        $this->quote([$roll->id => 3], 1430)
            ->assertJsonPath('gifts.0.posterId', 1430)
            ->assertJsonPath('gifts.0.name', 'Піца Тоні Монтана + Піца Карбонара')
            ->assertJsonPath('gifts.0.price', 410)
            ->assertJsonPath('gifts.0.items', [
                ['productId' => $montana->id, 'name' => 'Піца Тоні Монтана', 'price' => 195, 'image' => asset('images/products/test.webp')],
                ['productId' => $carbonara->id, 'name' => 'Піца Карбонара', 'price' => 215, 'image' => asset('images/products/test.webp')],
            ])
            ->assertJsonPath('giftOptions.1.name', 'Піца Тоні Монтана + Піца Карбонара');

        // у Poster — так само один набір 1430
        $this->postJson('/api/shop/add-order', $this->order([$roll->id => 3], 900) + ['gift' => 1430])->assertOk();
        $this->assertSame([['id' => 1430, 'count' => 1]], $this->sent[0]['promotion'][0]['result_products']);
    }

    public function test_thursday_gift_can_be_shown_by_hidden_site_card(): void
    {
        Carbon::setTestNow('2026-11-26 14:00:00');
        $roll = $this->product('Філадельфія', 300, 601, self::CAT_ROLLS);
        // картка лише для показу подарунка: прихована, без Poster ID
        $card = Product::query()->create([
            'title' => 'Червоний дракон з лососем', 'price' => 230, 'weight' => 250, 'count' => 1, 'isActive' => false,
            'main_image' => 'images/uploads/red-dragon.webp',
        ]);
        PromoDay::query()->where('weekday', 4)->update(['gift_display' => json_encode([['poster_id' => 702, 'products' => [$card->id]]])]);

        $this->quote([$roll->id => 2])
            ->assertJsonPath('applied', false)
            ->assertJsonPath('hint', 'Додайте страв ще на 300 грн — і Червоний дракон з лососем у подарунок');
        $this->quote([$roll->id => 3])
            ->assertJsonPath('applied', true)
            ->assertJsonPath('gifts.0.posterId', 702)
            ->assertJsonPath('gifts.0.productId', $card->id)
            ->assertJsonPath('gifts.0.name', 'Червоний дракон з лососем')
            ->assertJsonPath('gifts.0.items.0.image', asset('images/uploads/red-dragon.webp'));

        $this->postJson('/api/shop/add-order', $this->order([$roll->id => 3], 900))->assertOk();
        $this->assertSame([['id' => 702, 'count' => 1]], $this->sent[0]['promotion'][0]['result_products']);
    }

    public function test_wednesday_rolls_two_plus_one_makes_cheapest_roll_free(): void
    {
        Carbon::setTestNow('2026-11-25 14:00:00');
        $cheap = $this->product('Каліфорнія', 200, 601, self::CAT_ROLLS);
        $mid = $this->product('Філадельфія', 300, 602, self::CAT_ROLLS);
        $top = $this->product('Дракон', 400, 603, self::CAT_ROLLS);
        $set = $this->product('Сет', 900, 501, self::CAT_SETS);

        $this->quote([$mid->id => 2, $set->id => 1])
            ->assertJsonPath('applied', false)
            ->assertJsonPath('hint', 'Додайте ще 1 позицію з «Роли» — найдешевша буде у подарунок');

        $this->quote([$cheap->id => 1, $mid->id => 1, $top->id => 1, $set->id => 1])
            ->assertJsonPath('applied', true)
            ->assertJsonPath('discount', 200)
            ->assertJsonPath('extraDiscount', 200)
            ->assertJsonPath('freeItems', [['productId' => $cheap->id, 'count' => 1]])
            ->assertJsonPath('hint', null);

        // 5 ролів: одна група 2+1, ще 2 — бракує лише безкоштовного
        $this->quote([$mid->id => 5])
            ->assertJsonPath('freeItems', [['productId' => $mid->id, 'count' => 1]])
            ->assertJsonPath('hint', 'Додайте ще 1 позицію з «Роли» — і ще одна буде у подарунок');
        // 6 ролів — два безкоштовні
        $this->quote([$mid->id => 6])->assertJsonPath('freeItems', [['productId' => $mid->id, 'count' => 2]])->assertJsonPath('discount', 600);

        $this->postJson('/api/shop/add-order', $this->order([$cheap->id => 1, $mid->id => 1, $top->id => 1], 700))->assertOk();
        // безкоштовний рол каса додасть сама як результат акції
        $this->assertSame([['product_id' => 602, 'count' => 1], ['product_id' => 603, 'count' => 1]], $this->sent[0]['products']);
        $this->assertSame([[
            'id' => 94,
            'involved_products' => [['id' => 603, 'count' => 1], ['id' => 602, 'count' => 1]],
            'result_products' => [['id' => 601, 'count' => 1]],
        ]], $this->sent[0]['promotion']);
        $this->assertStringContainsString('Poster #94): Безкоштовно: Каліфорнія ×1. Знижка 200 грн. Сума з сайту: 700 грн', $this->sent[0]['comment']);
    }

    public function test_sunday_gift_for_every_set(): void
    {
        Carbon::setTestNow('2026-11-29 14:00:00');
        $set = $this->product('Сет Дует', 1000, 501, self::CAT_SETS);

        $this->quote([$set->id => 2])
            ->assertJsonPath('applied', true)
            ->assertJsonPath('gifts.0.name', 'Піца Тоні Монтана')
            ->assertJsonPath('gifts.0.count', 2)
            ->assertJsonPath('gifts.0.productId', null);
    }

    public function test_days_without_poster_promotion_and_outside_hours_are_inactive(): void
    {
        $set = $this->product('Сет', 1000, 501, self::CAT_SETS);

        Carbon::setTestNow('2026-11-27 14:00:00'); // п'ятниця — без акції Poster
        $this->quote([$set->id => 1])->assertExactJson(['active' => false]);

        Carbon::setTestNow('2026-11-23 21:00:00'); // понеділок після 20:30
        $this->quote([$set->id => 1])->assertExactJson(['active' => false]);

        // акцію в Poster завершено
        Carbon::setTestNow('2026-12-28 14:00:00');
        $this->quote([$set->id => 1])->assertExactJson(['active' => false]);
        $this->assertSame([], $this->getJson('/api/shop/promo-days')->json('days.0.discountProductIds'));
    }

    public function test_order_is_resent_without_promotion_when_poster_rejects_it(): void
    {
        Carbon::setTestNow('2026-11-25 14:00:00');
        $roll = $this->product('Філадельфія', 300, 602, self::CAT_ROLLS);
        $this->rejectAttempts = [1];

        $this->postJson('/api/shop/add-order', $this->order([$roll->id => 3], 600))->assertOk();

        $this->assertCount(2, $this->sent);
        $this->assertSame([], $this->sent[1]['promotion']);
        $this->assertSame([['product_id' => 602, 'count' => 3]], $this->sent[1]['products']);
        $this->assertStringContainsString('Акцію не застосовано автоматично', $this->sent[1]['comment']);
    }

    private function quote(array $lines, ?int $gift = null)
    {
        return $this->postJson('/api/shop/promo-quote', [
            'lines' => collect($lines)->map(fn ($q, $id) => ['id' => $id, 'quantity' => $q])->values()->all(),
            'gift' => $gift,
        ])->assertOk();
    }

    private function product(string $title, float $price, int $posterId, int $posterCategory): Product
    {
        $this->posterProducts[] = $this->posterProduct($posterId, $title, $posterCategory, $price);

        return Product::query()->create([
            'title' => $title, 'price' => $price, 'weight' => 250, 'count' => 1, 'isActive' => true,
            'main_image' => 'images/products/test.webp', 'poster_id' => $posterId,
        ]);
    }

    private function posterProduct(int $id, string $name, int $category, float $price): array
    {
        return [
            'product_id' => (string) $id, 'product_name' => $name, 'menu_category_id' => (string) $category,
            'category_name' => [self::CAT_ROLLS => 'Роли', self::CAT_SETS => 'Сети'][$category] ?? 'Інше',
            'price' => ['4' => (string) ($price * 100)],
        ];
    }

    private function fakePoster(): void
    {
        $promotions = [
            91 => $this->promotion(91, 1, [['type' => 1, 'id' => self::CAT_SETS, 'pcs' => 1, 'sum' => 0]], '2', '3', discount: '15', end: '2026-12-25 00:59:59'),
            93 => $this->promotion(93, 2, [['type' => 0, 'id' => 0, 'pcs' => 0, 'sum' => 70000]], '1', '1', bonus: [['type' => '2', 'id' => '977'], ['type' => '2', 'id' => '1430']]),
            94 => $this->promotion(94, 3, [['type' => 1, 'id' => self::CAT_ROLLS, 'pcs' => 2, 'sum' => 0]], '2', '1', bonus: [['type' => '1', 'id' => (string) self::CAT_ROLLS]]),
            95 => $this->promotion(95, 4, [['type' => 0, 'id' => 0, 'pcs' => 0, 'sum' => 90000]], '1', '1', bonus: [['type' => '2', 'id' => '702']]),
            96 => $this->promotion(96, 7, [['type' => 1, 'id' => self::CAT_SETS, 'pcs' => 1, 'sum' => 0]], '2', '1', bonus: [['type' => '2', 'id' => '1295']]),
        ];
        $poster = Mockery::mock(PosterAuthController::class);
        $poster->shouldReceive('getPromotion')->andReturnUsing(fn (int $id) => ['response' => $promotions[$id] ?? null]);
        $poster->shouldReceive('getMenuProducts')->andReturnUsing(fn () => ['response' => [
            ...$this->posterProducts,
            $this->posterProduct(1430, 'Дві піци', 99, 435),
            $this->posterProduct(1295, 'Піца Тоні Монтана ', 99, 220),
            $this->posterProduct(702, 'Червоний дракон', 99, 150),
        ]]);
        $poster->shouldReceive('createIncomingOrder')->andReturnUsing(function ($products, $customer, $delivery, $comment, $promotion = []) {
            $this->sent[] = compact('products', 'comment', 'promotion');

            return in_array(count($this->sent), $this->rejectAttempts, true)
                ? ['error' => 32, 'message' => 'promotion error']
                : ['response' => ['incoming_order_id' => count($this->sent)]];
        });
        $this->app->instance(PosterAuthController::class, $poster);
    }

    private function promotion(int $id, int $weekday, array $conditions, string $accumulation, string $result, string $discount = '0', array $bonus = [], string $end = '0000-00-00 00:00:00'): array
    {
        $weekDays = array_fill(0, 7, '0');
        $weekDays[$weekday - 1] = '1';

        return [
            'promotion_id' => (string) $id,
            'name' => "Акція {$id}",
            'params' => [
                'conditions' => $conditions,
                'conditions_rule' => 'or',
                'conditions_exactly' => 1,
                'accumulation_type' => $accumulation,
                'week_days' => $weekDays,
                'periods' => [['start' => '00:00', 'end' => '23:59']],
                'result_type' => $result,
                'bonus_products' => $bonus,
                'bonus_products_pcs' => '1',
                'bonus_products_condition_type' => $bonus ? '1' : 0,
                'bonus_products_condition_value' => $bonus ? '100' : 0,
                'discount_value' => $discount,
                'available_for_spots' => ['4' => 1, '5' => 0, '6' => 0],
            ],
            'date_start' => '2026-11-23 01:00:00',
            'date_end' => $end,
            'auto_apply' => '0',
        ];
    }

    /** @param array<int, int> $lines */
    private function order(array $lines, int $sum): array
    {
        return [
            'customer' => [
                'name' => 'Тест', 'phone' => '+380991112233', 'delivery' => 'самовивiz', 'street' => '', 'building' => '',
                'payment' => 'готівка', 'sticks' => ['educational' => 0, 'standard' => 1],
                'time' => ['day' => '', 'time' => ''], 'isAsSoonAsPossible' => 'true',
            ],
            'products' => collect($lines)->map(fn ($q, $id) => ['id' => $id, 'quantity' => $q])->values()->all(),
            'sum' => $sum,
        ];
    }
}
