<?php

namespace Tests\Feature;

use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PosterOrderAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_token' => 'test-token', 'services.telegram.alert_chat_id' => '-100123']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    public function test_rejected_order_sends_telegram_alert_and_customer_sees_success(): void
    {
        $this->fakePoster(['error' => 30, 'message' => 'Product not found']);

        $this->postJson('/api/shop/add-order', $this->payload())->assertOk()->assertJsonPath('data.customer_name', 'Олена');

        $order = Order::query()->sole();
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($order) {
            $text = $request['text'];

            return str_contains($request->url(), 'bottest-token/sendMessage')
                && $request['chat_id'] === '-100123'
                && str_contains($text, "#{$order->id}")
                && str_contains($text, 'Олена')
                && str_contains($text, '+380991112233')
                && str_contains($text, 'Центральна 5')
                && str_contains($text, 'Запечена Каліфорнія × 2')
                && str_contains($text, 'Poster 965')
                && str_contains($text, '440 грн')
                && str_contains($text, 'Product not found');
        });
    }

    public function test_curl_failure_is_also_alerted(): void
    {
        $this->fakePoster('CURL Error:Operation timed out');

        $this->postJson('/api/shop/add-order', $this->payload())->assertOk();

        Http::assertSent(fn (Request $request) => str_contains($request['text'], 'Operation timed out'));
    }

    public function test_accepted_order_sends_nothing(): void
    {
        $this->fakePoster(['response' => ['incoming_order_id' => 37177]]);

        $this->postJson('/api/shop/add-order', $this->payload())->assertOk();

        Http::assertNothingSent();
    }

    public function test_telegram_failure_does_not_break_order(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);
        $this->fakePoster(['error' => 30]);

        $this->postJson('/api/shop/add-order', $this->payload())->assertOk();

        $this->assertSame(1, Order::query()->count());
    }

    private function fakePoster(mixed $response): void
    {
        $poster = Mockery::mock(PosterAuthController::class);
        $poster->shouldReceive('createIncomingOrder')->andReturn($response);
        $this->app->instance(PosterAuthController::class, $poster);
    }

    private function payload(): array
    {
        $product = Product::query()->create([
            'title' => 'Запечена Каліфорнія', 'price' => 220, 'weight' => 250, 'count' => 1,
            'main_image' => 'images/products/test.webp', 'poster_id' => 965,
        ]);

        return [
            'customer' => [
                'name' => 'Олена',
                'phone' => '+380991112233',
                'delivery' => 'курʼєр',
                'street' => 'Центральна',
                'building' => '5',
                'payment' => 'термінал',
                'sticks' => ['educational' => 0, 'standard' => 2],
                'time' => ['day' => '', 'time' => ''],
                'isAsSoonAsPossible' => 'true',
            ],
            'products' => [['id' => $product->id, 'quantity' => 2]],
            'sum' => 440,
        ];
    }
}
