<?php

namespace App\Services\Promo;

use App\Http\Controllers\Utils\PosterAuthController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Акції й товари з Poster з кешем. Якщо Poster не відповідає — беремо останню вдалу копію,
 * щоб сайт і далі рахував акцію; без жодної копії акція дня працює по-старому (рядок у коментарі).
 */
class PosterPromotions
{
    private const TTL = 600;

    public function __construct(private readonly PosterAuthController $poster) {}

    public function find(int $promotionId): ?PosterPromotion
    {
        $raw = $this->remember("poster-promotion:{$promotionId}", function () use ($promotionId) {
            $response = $this->poster->getPromotion($promotionId);

            return is_array($response['response'] ?? null) ? $response['response'] : null;
        });

        return $raw ? PosterPromotion::fromPoster($raw) : null;
    }

    /**
     * Товари Poster: product_id => [category_id, category, name, price (грн, точка сайту)].
     *
     * @return array<int, array{category_id: int, category: string, name: string, price: float}>
     */
    public function products(): array
    {
        return $this->remember('poster-products', function () {
            $response = $this->poster->getMenuProducts();
            if (! is_array($response['response'] ?? null)) {
                return null;
            }
            $products = [];
            foreach ($response['response'] as $item) {
                $price = $item['price'][PosterPromotion::SPOT_ID] ?? (is_array($item['price'] ?? null) ? reset($item['price']) : 0);
                $products[(int) $item['product_id']] = [
                    'category_id' => (int) $item['menu_category_id'],
                    'category' => trim((string) ($item['category_name'] ?? '')),
                    'name' => trim(preg_replace('/\s+/u', ' ', $item['product_name'])),
                    'price' => round(((int) $price) / 100, 2),
                ];
            }

            return $products ?: null;
        }) ?? [];
    }

    /** Свіжа копія на TTL, остання вдала — назавжди як запасна. */
    private function remember(string $key, callable $fetch): ?array
    {
        $fresh = Cache::get($key);
        if ($fresh !== null) {
            return $fresh;
        }
        if (Cache::has("{$key}:retry")) {
            return Cache::get("{$key}:last");
        }
        try {
            $value = $fetch();
        } catch (Throwable $e) {
            Log::warning("Poster: {$key} не завантажено", ['error' => $e->getMessage()]);
            $value = null;
        }
        if ($value !== null) {
            Cache::put($key, $value, self::TTL);
            Cache::forever("{$key}:last", $value);

            return $value;
        }

        Cache::put("{$key}:retry", true, 60);

        return Cache::get("{$key}:last");
    }
}
