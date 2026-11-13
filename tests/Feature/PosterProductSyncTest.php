<?php

namespace Tests\Feature;

use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Product;
use App\Services\PosterProductSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PosterProductSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_disables_missing_and_hidden_enables_present_and_creates_new(): void
    {
        $present = $this->product('Філадельфія', 101, active: false, price: 300);
        $missing = $this->product('Запечена Каліфорнія', 965);
        $hidden = $this->product('Сирний рол', 212);
        $offSpot = $this->product('Каліфорнія спайсі', 33);

        $this->fakePoster([
            3 => [
                $this->item(101, 'Філадельфія', 32000),
                $this->item(212, 'Сирний рол', 20000, hidden: true),
                $this->item(33, 'Каліфорнія спайсі', 20000, visibleOnSpot: false),
                $this->item(1410, 'Запечена Каліфорнія в кунжуті', 22000),
            ],
        ]);

        $plan = app(PosterProductSync::class)->run(withImages: false);

        $this->assertEqualsCanonicalizing([$missing->id, $hidden->id, $offSpot->id], $plan['disable']->pluck('id')->all());
        $this->assertSame([$present->id], $plan['enable']->pluck('id')->all());

        $this->assertTrue($present->fresh()->isActive);
        $this->assertEquals(320, $present->fresh()->price);
        $this->assertFalse($missing->fresh()->isActive);
        $this->assertFalse($hidden->fresh()->isActive);
        $this->assertFalse($offSpot->fresh()->isActive);

        $new = Product::query()->where('poster_id', 1410)->first();
        $this->assertNotNull($new);
        $this->assertTrue($new->isActive);
        $this->assertEquals(220, $new->price);
        $this->assertDatabaseHas('category_product_shop', ['product_id' => $new->id, 'category_id' => 5]);
    }

    public function test_poster_error_changes_nothing(): void
    {
        $product = $this->product('Філадельфія', 101);
        $this->fakePoster([3 => [$this->item(101, 'Філадельфія', 32000)]], failCategory: 5);

        try {
            app(PosterProductSync::class)->run(withImages: false);
            $this->fail('Expected exception');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('нічого не змінено', $e->getMessage());
        }

        $this->assertTrue($product->fresh()->isActive);
    }

    public function test_suspiciously_small_menu_is_rejected(): void
    {
        $products = collect(range(1, 4))->map(fn ($i) => $this->product("Рол {$i}", 100 + $i));
        $this->fakePoster([3 => [$this->item(101, 'Рол 1', 10000)]]);

        $this->expectException(RuntimeException::class);
        try {
            app(PosterProductSync::class)->run(withImages: false);
        } finally {
            $this->assertSame(4, Product::query()->where('isActive', true)->count());
        }
    }

    public function test_dry_run_command_changes_nothing(): void
    {
        $missing = $this->product('Запечена Каліфорнія', 965);
        $this->product('Філадельфія', 101);
        $this->fakePoster([3 => [$this->item(101, 'Філадельфія', 32000)]]);

        $this->artisan('poster:sync-products', ['--dry-run' => true])
            ->expectsOutputToContain('Пробний прогін')
            ->expectsOutputToContain('Запечена Каліфорнія')
            ->assertSuccessful();

        $this->assertTrue($missing->fresh()->isActive);
    }

    private function product(string $title, int $posterId, bool $active = true, float $price = 100): Product
    {
        $product = new Product();
        $product->forceFill([
            'title' => $title, 'price' => $price, 'weight' => 300, 'count' => 8,
            'poster_id' => $posterId, 'isActive' => $active, 'main_image' => 'images/x.webp',
        ])->save();

        return $product;
    }

    private function item(int $id, string $name, int $price, bool $hidden = false, bool $visibleOnSpot = true): array
    {
        return [
            'product_id' => (string) $id,
            'product_name' => $name,
            'category_name' => 'Роли',
            'hidden' => $hidden ? '1' : '0',
            'price' => ['4' => (string) $price],
            'spots' => [['spot_id' => '4', 'price' => (string) $price, 'visible' => $visibleOnSpot ? '1' : '0']],
            'out' => 250,
            'photo_origin' => null,
            'product_production_description' => '',
        ];
    }

    private function fakePoster(array $byCategory, ?int $failCategory = null): void
    {
        $poster = Mockery::mock(PosterAuthController::class)->makePartial();
        $poster->shouldReceive('menuProducts')->andReturnUsing(
            fn (int $category) => $category === $failCategory ? 'CURL Error: timeout' : ['response' => $byCategory[$category] ?? []]
        );
        $this->app->instance(PosterAuthController::class, $poster);
    }
}
