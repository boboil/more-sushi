<?php

namespace Tests\Feature;

use App\Filament\Resources\Shop\PromoDays\Pages\EditPromoDay;
use App\Filament\Resources\Shop\PromoDays\Pages\ListPromoDays;
use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Role;
use App\Models\Shop\Product;
use App\Models\Shop\PromoDay;
use App\Models\User;
use App\Services\Promo\PromoDays;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class PromoDayTest extends TestCase
{
    use RefreshDatabase;

    // четвер, 14:00 за Києвом
    private const THURSDAY = '2026-11-12 14:00:00';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_migration_seeds_seven_days_and_landing_is_off(): void
    {
        $this->assertSame(range(1, 7), PromoDay::query()->orderBy('weekday')->pluck('weekday')->all());
        $this->assertFalse(app(PromoDays::class)->enabled());

        $this->getJson('/api/shop/promo-days')->assertOk()->assertExactJson(['enabled' => false, 'days' => []]);
    }

    public function test_api_returns_days_when_enabled(): void
    {
        $product = $this->product('Філадельфія', 320);
        $thursday = PromoDay::query()->where('weekday', 4)->first();
        $thursday->products()->attach($product->id);
        app(PromoDays::class)->setEnabled(true);

        $response = $this->getJson('/api/shop/promo-days')->assertOk();

        $response->assertJsonPath('enabled', true)->assertJsonCount(7, 'days');
        $response->assertJsonPath('days.3.key', 'thu')
            ->assertJsonPath('days.3.slug', 'chetver')
            ->assertJsonPath('days.3.discountPercent', 25)
            ->assertJsonPath('days.3.productIds', [$product->id])
            ->assertJsonPath('days.6.offerType', 'gift')
            ->assertJsonPath('days.6.discountPercent', null);
    }

    public function test_current_promo_respects_flag_day_and_hours(): void
    {
        $promo = app(PromoDays::class);
        Carbon::setTestNow(self::THURSDAY);
        $this->assertNull($promo->current(), 'вимкнено перемикачем');

        $promo->setEnabled(true);
        $this->assertSame(4, $promo->current()?->weekday);

        Carbon::setTestNow('2026-11-12 20:45:00');
        $this->assertNull($promo->current(), 'після 20:30');

        Carbon::setTestNow(self::THURSDAY);
        PromoDay::query()->where('weekday', 4)->update(['is_active' => false]);
        $this->assertNull($promo->current(), 'день вимкнено');
    }

    public function test_poster_comment_mentions_promo_only_for_promo_products(): void
    {
        Carbon::setTestNow(self::THURSDAY);
        app(PromoDays::class)->setEnabled(true);
        $salmon = $this->product('Рол з лососем', 300);
        $other = $this->product('Піца', 200);
        PromoDay::query()->where('weekday', 4)->first()->products()->attach($salmon->id);

        $comments = [];
        $poster = Mockery::mock(PosterAuthController::class);
        $poster->shouldReceive('createIncomingOrder')->andReturnUsing(function ($p, $c, $d, $comment) use (&$comments) {
            $comments[] = $comment;

            return ['response' => ['incoming_order_id' => 1]];
        });
        $this->app->instance(PosterAuthController::class, $poster);

        $this->postJson('/api/shop/add-order', $this->order($salmon, 225))->assertOk();
        $this->postJson('/api/shop/add-order', $this->order($other, 200))->assertOk();

        $this->assertStringContainsString('АКЦІЯ ДНЯ (Четвер): -25% на роли з лососем. Товари: Рол з лососем. Сума з сайту: 225 грн', $comments[0]);
        $this->assertStringContainsString("Как можно быстрее\n", $comments[0]);
        $this->assertStringNotContainsString('АКЦІЯ ДНЯ', $comments[1]);
    }

    public function test_comment_for_days_without_products(): void
    {
        Carbon::setTestNow(self::THURSDAY);
        app(PromoDays::class)->setEnabled(true);
        $controller = app(\App\Http\Controllers\Shop\OrderController::class);

        // знижка без товарів ні на що не діє
        $this->assertSame('', $controller->promoDayComment([1], 500));

        // подарунок без списку товарів — на будь-яке замовлення
        PromoDay::query()->where('weekday', 4)->update(['offer_type' => 'gift', 'discount_percent' => null, 'gift_badge' => '1+1', 'offer_title' => 'десерт у подарунок']);
        $this->assertSame('АКЦІЯ ДНЯ (Четвер): 1+1 десерт у подарунок. Сума з сайту: 500 грн', $controller->promoDayComment([1], 500));
    }

    public function test_admin_can_edit_day_and_toggle_landing(): void
    {
        $this->actingAs($this->admin());
        $product = $this->product('Філадельфія', 320);
        $friday = PromoDay::query()->where('weekday', 5)->first();

        $this->get('/admin/promo-days')->assertOk()->assertSee('Увімкнути лендинги');
        $this->get('/admin/promo-days/' . $friday->id . '/edit')->assertOk();

        Livewire::test(EditPromoDay::class, ['record' => $friday->getKey()])
            ->set('data.discount_percent', 35)
            ->set('data.active_to', '22:00')
            ->set('data.products', [(string) $product->id])
            ->call('save')
            ->assertHasNoErrors();
        $friday->refresh();
        $this->assertSame(35, $friday->discount_percent);
        $this->assertSame('22:00', $friday->active_to);
        $this->assertSame([$product->id], $friday->products->pluck('id')->all());

        Livewire::test(EditPromoDay::class, ['record' => $friday->getKey()])
            ->set('data.active_to', '25:00')
            ->call('save')
            ->assertHasErrors(['data.active_to']);

        Livewire::test(ListPromoDays::class)->callAction('togglePromoLanding');
        $this->assertTrue(app(PromoDays::class)->enabled());
        Livewire::test(ListPromoDays::class)->callAction('togglePromoLanding');
        $this->assertFalse(app(PromoDays::class)->enabled());
    }

    private function product(string $title, float $price): Product
    {
        return Product::query()->create([
            'title' => $title, 'price' => $price, 'weight' => 250, 'count' => 1,
            'main_image' => 'images/products/test.webp', 'poster_id' => random_int(1, 99999),
        ]);
    }

    private function order(Product $product, int $sum): array
    {
        return [
            'customer' => [
                'name' => 'Тест', 'phone' => '+380991112233', 'delivery' => 'самовивiz', 'street' => '', 'building' => '',
                'payment' => 'готівка', 'sticks' => ['educational' => 0, 'standard' => 1],
                'time' => ['day' => '', 'time' => ''], 'isAsSoonAsPossible' => 'true',
            ],
            'products' => [['id' => $product->id, 'quantity' => 1]],
            'sum' => $sum,
        ];
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->create(['name' => 'admin', 'label' => 'Адміністратор']));

        return $user;
    }
}
