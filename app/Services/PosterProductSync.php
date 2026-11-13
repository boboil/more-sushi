<?php

namespace App\Services;

use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\Shop\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Синхронізація товарів сайту з Poster (кнопка «Синхронізувати з Poster»).
 *
 * На сайті показуються лише товари, які є в Poster у потрібних категоріях і не приховані
 * для нашої точки: решту вимикаємо, бо Poster відхиляє замовлення з неіснуючим товаром.
 * Спочатку забираємо все з Poster, і лише якщо всі запити вдалися — змінюємо базу однією транзакцією.
 */
class PosterProductSync
{
    /** Категорія Poster => категорія сайту. */
    public const CATEGORIES = [3 => 5, 5 => 1, 7 => 3, 15 => 4, 18 => 6, 26 => 8, 23 => 7];

    /** Точка, з якої беремо ціну й видимість і на яку йдуть замовлення сайту. */
    public const SPOT_ID = '4';

    /** Якщо в Poster доступно менше цієї частки від увімкнених зараз товарів — вважаємо це збоєм. */
    private const MIN_SHARE = 0.5;

    public function __construct(private readonly PosterAuthController $poster) {}

    /**
     * @return array{enable: Collection, disable: Collection, create: Collection, update: Collection}
     */
    public function plan(): array
    {
        return $this->diff($this->fetch());
    }

    /**
     * @return array{enable: Collection, disable: Collection, create: Collection, update: Collection}
     */
    public function run(bool $withImages = true): array
    {
        $items = $this->fetch();
        $plan = $this->diff($items);

        DB::transaction(function () use ($items, $withImages): void {
            Product::query()->where('isActive', true)->update(['isActive' => false]);

            foreach ($items as $item) {
                $product = Product::getProductsByPosterId($item['product_id']);
                if (! $product) {
                    $product = new Product();
                    $product->title = $item['product_name'];
                    $product->count = 1;
                    $product->weight = $item['out'];
                    $product->consist = '';
                    $product->stock = 0;
                    $product->latest = 0;
                    $product->description = $item['product_production_description'] ?? '';
                    $product->slug = Str::slug($item['product_name']);
                    $product->isRelated = 0;
                    $product->poster_id = $item['product_id'];
                }
                $product->price = $this->poster->transformPrice($this->price($item));
                $product->isActive = true;
                if ($withImages && filled($item['photo_origin'] ?? null)) {
                    $product->main_image = $this->image($item['photo_origin']) ?? $product->main_image;
                }
                $isNew = ! $product->exists;
                $product->save();
                if ($isNew) {
                    $product->category()->attach($item['site_category_id']);
                }
            }
        });

        Log::info('Poster sync', [
            'enabled' => $plan['enable']->count(),
            'disabled' => $plan['disable']->count(),
            'created' => $plan['create']->count(),
        ]);

        return $plan;
    }

    /**
     * Доступні товари з усіх категорій. Будь-яка помилка Poster — виняток, база не змінюється.
     *
     * @return Collection<string, array> ключ — poster product_id
     */
    public function fetch(): Collection
    {
        $items = collect();
        foreach (self::CATEGORIES as $posterCategory => $siteCategory) {
            $response = $this->poster->menuProducts($posterCategory);
            if (! is_array($response) || ! is_array($response['response'] ?? null)) {
                throw new RuntimeException("Poster не віддав товари категорії {$posterCategory}. Синхронізацію скасовано, нічого не змінено.");
            }
            foreach ($response['response'] as $item) {
                if ($this->available($item) && ! $items->has((string) $item['product_id'])) {
                    $items->put((string) $item['product_id'], $item + ['site_category_id' => $siteCategory]);
                }
            }
        }

        $active = Product::query()->where('isActive', true)->count();
        if ($items->isEmpty() || $items->count() < $active * self::MIN_SHARE) {
            throw new RuntimeException("Poster повернув лише {$items->count()} доступних товарів (на сайті зараз {$active}). Схоже на збій, синхронізацію скасовано.");
        }

        return $items;
    }

    private function diff(Collection $items): array
    {
        $products = Product::query()->get(['id', 'title', 'poster_id', 'isActive', 'price']);
        $byPoster = $products->filter(fn (Product $p) => filled($p->poster_id))->keyBy(fn (Product $p) => (string) $p->poster_id);

        return [
            'enable' => $products->filter(fn (Product $p) => ! $p->isActive && $items->has((string) $p->poster_id))->values(),
            'disable' => $products->filter(fn (Product $p) => $p->isActive && ! $items->has((string) $p->poster_id))->values(),
            'create' => $items->reject(fn (array $item, string $id) => $byPoster->has($id))->values(),
            'update' => $items->filter(fn (array $item, string $id) => $byPoster->has($id)
                && (float) $byPoster[$id]->price !== (float) $this->poster->transformPrice($this->price($item)))
                ->map(fn (array $item, string $id) => ['product' => $byPoster[$id], 'price' => $this->poster->transformPrice($this->price($item))])
                ->values(),
        ];
    }

    private function available(array $item): bool
    {
        if ((string) ($item['hidden'] ?? '0') === '1') {
            return false;
        }
        $spot = collect($item['spots'] ?? [])->first(fn ($spot) => (string) ($spot['spot_id'] ?? '') === self::SPOT_ID);

        return $spot === null || (string) ($spot['visible'] ?? '1') === '1';
    }

    private function price(array $item): mixed
    {
        return $item['price'][self::SPOT_ID] ?? $item['price'][(int) self::SPOT_ID] ?? 0;
    }

    private function image(string $url): ?string
    {
        try {
            return $this->poster->saveImage($url);
        } catch (Throwable $e) {
            Log::warning('Poster sync: image download failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null; // лишаємо стару картинку
        }
    }
}
