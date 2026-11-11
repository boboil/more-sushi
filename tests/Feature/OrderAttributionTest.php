<?php

namespace Tests\Feature;

use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderAttributionTest extends TestCase
{
    use RefreshDatabase;

    private string $posterComment = '';

    protected function setUp(): void
    {
        parent::setUp();
        // замовлення не йде в Poster; запам'ятовуємо коментар, щоб перевірити, що мітки туди не потрапляють
        $poster = Mockery::mock(PosterAuthController::class);
        $poster->shouldReceive('createIncomingOrder')->andReturnUsing(function ($products, $customer, $delivery, $comment) {
            $this->posterComment = $comment;

            return ['response' => ['incoming_order_id' => 1]];
        });
        $this->app->instance(PosterAuthController::class, $poster);
    }

    public function test_attribution_is_sanitized_and_saved(): void
    {
        $this->postJson('/api/shop/add-order', $this->payload([
            'attribution' => [
                'source' => 'chatgpt',
                'medium' => 'cpc',
                'campaign' => 'rolls-test-nov',
                'landing_page' => '/menu/roly',
                'email' => 'spam@example.com',
                'term' => ['nested'],
            ],
        ]))->assertOk();

        $order = Order::query()->sole();
        $this->assertSame(
            ['source' => 'chatgpt', 'medium' => 'cpc', 'campaign' => 'rolls-test-nov', 'landing_page' => '/menu/roly'],
            $order->attribution,
        );
        $this->assertStringNotContainsString('chatgpt', $this->posterComment);
    }

    public function test_order_without_attribution_is_still_created(): void
    {
        $this->postJson('/api/shop/add-order', $this->payload())->assertOk();
        $this->postJson('/api/shop/add-order', $this->payload(['attribution' => 'utm_source=x']))->assertOk();

        $this->assertSame([null, null], Order::query()->pluck('attribution')->all());
    }

    private function payload(array $extra = []): array
    {
        $product = Product::query()->create([
            'title' => 'Філадельфія', 'price' => 300, 'weight' => 250, 'count' => 1,
            'main_image' => 'images/products/test.webp', 'poster_id' => 10,
        ]);

        return [
            'customer' => [
                'name' => 'Тест',
                'phone' => '+380991112233',
                'delivery' => 'самовивiz',
                'street' => '',
                'building' => '',
                'payment' => 'готівка',
                'sticks' => ['educational' => 0, 'standard' => 1],
                'time' => ['day' => '', 'time' => ''],
                'isAsSoonAsPossible' => 'true',
            ],
            'products' => [['id' => $product->id, 'quantity' => 2]],
            'sum' => 600,
            ...$extra,
        ];
    }
}
