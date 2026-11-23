<?php

namespace App\Services\Promo;

use App\Models\Shop\Product;
use App\Models\Shop\PromoDay;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Рахує «Акцію дня» за правилами акції Poster, щоб сайт давав рівно те, що дасть каса:
 * умови (сума замовлення / N товарів категорії чи товару), повтор у межах чека,
 * результат (знижка % на товари з умови, подарунковий товар або безкоштовний товар із кошика).
 */
class PromoCart
{
    public function __construct(private readonly PosterPromotions $poster) {}

    /**
     * Акція Poster для цього дня, якщо вона прив'язана, діє сьогодні й сайт уміє її порахувати.
     * ignoreWeekday — для опису всіх днів тижня: перевіряємо лише період дії.
     */
    public function promotionFor(PromoDay $day, ?CarbonInterface $now = null, bool $ignoreWeekday = false): ?PosterPromotion
    {
        if (! $day->poster_promotion_id) {
            return null;
        }
        $promotion = $this->poster->find($day->poster_promotion_id);

        return $promotion && $promotion->supported() && $promotion->activeOn($now ?? now(), $ignoreWeekday) ? $promotion : null;
    }

    /**
     * Знижка ціною в меню: лише коли кожна штука товару з умови отримує % без інших умов
     * (як «-15% на всі сети»). Інакше знижку видно лише в кошику.
     *
     * @return list<int> товари сайту
     */
    public function discountProductIds(PosterPromotion $promotion): array
    {
        if ($promotion->resultType !== PosterPromotion::RESULT_PERCENT || ! $promotion->repeat || $promotion->allConditions) {
            return [];
        }
        $conditions = collect($promotion->conditions);
        if ($conditions->contains(fn ($c) => $c['type'] === PosterPromotion::CONDITION_SUM || $c['pcs'] !== 1)) {
            return [];
        }
        $posterProducts = $this->poster->products();

        return Product::query()->where('isActive', true)->whereNotNull('poster_id')->get(['id', 'poster_id'])
            ->filter(fn (Product $p) => $conditions->contains(fn ($c) => $this->matches($c, (int) $p->poster_id, $posterProducts)))
            ->pluck('id')->values()->all();
    }

    /**
     * Опис акції Poster для лендингу: умови словами, подарунки й товари, які треба купити.
     *
     * @return array{text: string, gifts: list<array>, productIds: list<int>}
     *                                                                        productIds — товари з умови (категорія/товар); [] — умова на суму, підходить усе меню
     */
    public function describe(PromoDay $day, PosterPromotion $promotion): array
    {
        $posterProducts = $this->poster->products();
        $conditions = collect($promotion->conditions);
        $what = $conditions->map(fn (array $c) => match ($c['type']) {
            PosterPromotion::CONDITION_SUM => 'замовлення від ' . $this->num($c['sum']) . ' грн',
            PosterPromotion::CONDITION_CATEGORY => ($c['pcs'] > 1 ? "{$c['pcs']} " . $this->positions($c['pcs']) : 'кожну позицію') . ' з «' . $this->categoryName($c['id'], $posterProducts) . '»',
            default => '«' . ($posterProducts[$c['id']]['name'] ?? 'товар акції') . '»' . ($c['pcs'] > 1 ? " × {$c['pcs']}" : ''),
        })->implode($promotion->allConditions ? ' і ' : ' або ');
        $hasSum = $conditions->contains(fn ($c) => $c['type'] === PosterPromotion::CONDITION_SUM);
        $once = $promotion->repeat ? '' : ' Один подарунок на замовлення.';
        $gifts = [];

        if ($promotion->resultType === PosterPromotion::RESULT_PERCENT) {
            $text = 'Знижка ' . $this->num($promotion->discountPercent) . "% на {$what}. "
                . ($this->discountProductIds($promotion) ? 'Ціни в меню вже зі знижкою.' : 'Знижка з\'явиться в кошику автоматично.');
        } elseif ($promotion->bonusCategories() !== []) {
            $condition = $conditions->first();
            $category = $this->categoryName($promotion->bonusCategories()[0], $posterProducts);
            $group = $condition ? $condition['pcs'] + $promotion->bonusPcs : 0;
            $text = ($condition && $this->bonusSharesPool($promotion, $condition)
                ? "Візьміть {$group} " . $this->positions($group) . " з «{$category}» — найдешевша у подарунок."
                    . ($promotion->repeat ? ' Діє на кожні ' . $group . ' ' . $this->positions($group) . ' в замовленні.' : $once)
                : 'За ' . $what . " — позиція з «{$category}» у подарунок." . $once)
                . ' У кошику вона буде окремим рядком по 0 грн.';
        } else {
            $gifts = collect($promotion->bonusProductIds())->map(fn (int $id) => $this->giftOption($day, $id, $posterProducts))->values()->all();
            $names = collect($gifts)->pluck('name')->implode(' або ');
            $condition = $hasSum ? mb_strtoupper(mb_substr($what, 0, 1)) . mb_substr($what, 1) : 'За ' . $what;
            $text = $condition . ' — ' . (count($gifts) > 1 ? "подарунок на вибір: {$names}" : "{$names} у подарунок") . '.' . $once
                . ' Подарунок з\'явиться в кошику автоматично по 0 грн' . (count($gifts) > 1 ? ', там же його можна обрати.' : '.');
        }

        return [
            'text' => $text,
            'gifts' => $gifts,
            'productIds' => $hasSum ? [] : Product::query()->where('isActive', true)->whereNotNull('poster_id')->orderBy('id')->get(['id', 'poster_id'])
                ->filter(fn (Product $p) => $conditions->contains(fn ($c) => $this->matches($c, (int) $p->poster_id, $posterProducts)))
                ->pluck('id')->values()->all(),
        ];
    }

    /**
     * @param  array<int, int>  $lines  товар сайту => кількість
     * @param  int|null  $gift  обраний подарунок (товар Poster), якщо варіантів кілька
     */
    public function quote(PromoDay $day, PosterPromotion $promotion, array $lines, ?int $gift = null): PromoQuote
    {
        $posterProducts = $this->poster->products();
        $units = $this->units($lines, $posterProducts);
        $subtotal = $units->sum('price');

        // кожна умова: скільки разів виконана і які штуки під неї підпадають
        $evaluated = collect($promotion->conditions)->map(function (array $c) use ($promotion, $units, $subtotal, $posterProducts) {
            if ($c['type'] === PosterPromotion::CONDITION_SUM) {
                $times = $c['sum'] <= 0 ? ($units->isEmpty() ? 0 : 1) : ($subtotal >= $c['sum'] ? 1 : 0);
                if ($times && $promotion->repeat && $c['sum'] > 0) {
                    $times = (int) floor($subtotal / $c['sum']);
                }

                return ['c' => $c, 'times' => $times, 'units' => $units, 'group' => 0, 'shared' => false];
            }
            $matching = $units->filter(fn ($u) => $this->matches($c, $u['poster'], $posterProducts))->values();
            // подарунок із тієї ж категорії: «2+1» — у групі 2 платні й 1 безкоштовний
            $shared = $promotion->resultType === PosterPromotion::RESULT_BONUS && $this->bonusSharesPool($promotion, $c);
            $group = $c['pcs'] + ($shared ? $promotion->bonusPcs : 0);
            $times = $promotion->repeat ? intdiv($matching->count(), $group) : ($matching->count() >= $group ? 1 : 0);

            return ['c' => $c, 'times' => $times, 'units' => $matching, 'group' => $group, 'shared' => $shared];
        });

        $chosen = $promotion->allConditions
            ? $evaluated->sortBy('times')->first()
            : $evaluated->sortByDesc('times')->first();
        $times = $promotion->allConditions ? (int) $evaluated->min('times') : (int) $chosen['times'];

        return match ($promotion->resultType) {
            PosterPromotion::RESULT_PERCENT => $this->percent($day, $promotion, $chosen, $times, $evaluated, $posterProducts),
            default => $promotion->bonusCategories() !== []
                ? $this->freeFromCart($day, $promotion, $chosen, $times, $units, $posterProducts)
                : $this->giftProducts($day, $promotion, $chosen, $times, $gift, $subtotal, $posterProducts),
        };
    }

    private function percent(PromoDay $day, PosterPromotion $promotion, array $chosen, int $times, Collection $evaluated, array $posterProducts): PromoQuote
    {
        $percent = $promotion->discountPercent;
        if ($times < 1) {
            return $this->notApplied($day, $promotion, $this->hint($chosen, $posterProducts, '— і знижка ' . $this->num($percent) . '%'));
        }
        $involved = $chosen['c']['type'] === PosterPromotion::CONDITION_SUM
            ? $chosen['units']
            : $chosen['units']->sortByDesc('price')->take($times * $chosen['c']['pcs']);
        $discount = (int) $involved->sum(fn ($u) => $u['price'] - round($u['price'] * (100 - $percent) / 100));
        $inPrice = $this->discountProductIds($promotion) !== [];

        return new PromoQuote(
            weekday: $day->weekday,
            posterPromotionId: $promotion->id,
            applied: true,
            discount: $discount,
            extraDiscount: $inPrice ? 0 : $discount,
            involved: $this->countBy($involved, 'poster'),
        );
    }

    /** Подарунок — найдешевші товари потрібної категорії з кошика («Роли 2+1»). */
    private function freeFromCart(PromoDay $day, PosterPromotion $promotion, array $chosen, int $times, Collection $units, array $posterProducts): PromoQuote
    {
        $categories = $promotion->bonusCategories();
        $inBonus = fn ($u) => in_array($posterProducts[$u['poster']]['category_id'] ?? null, $categories, true);
        $free = collect();
        $paid = collect();
        if ($times > 0) {
            $needFree = $times * $promotion->bonusPcs;
            if ($chosen['shared']) {
                $sorted = $chosen['units']->sortBy('price')->values();
                $free = $sorted->take($needFree);
                $paid = $sorted->slice($needFree)->sortByDesc('price')->take($times * $chosen['c']['pcs']);
            } else {
                $paid = $chosen['c']['type'] === PosterPromotion::CONDITION_SUM
                    ? $chosen['units']
                    : $chosen['units']->sortByDesc('price')->take($times * $chosen['c']['pcs']);
                $paidKeys = $paid->keys()->all();
                $free = $units->filter(fn ($u, $k) => $inBonus($u) && ! in_array($k, $paidKeys, true))->sortBy('price')->take($needFree);
            }
        }

        $hint = null;
        if ($free->isEmpty()) {
            $category = $this->categoryName($categories[0], $posterProducts);
            $reward = $chosen['shared'] ? '— найдешевша буде у подарунок' : "— і позиція з «{$category}» у подарунок";
            $hint = $times > 0
                ? "Додайте позицію з «{$category}» — вона буде у подарунок"
                : $this->hint($chosen, $posterProducts, $reward, $chosen['shared'] ? $chosen['group'] : null);

            return $this->notApplied($day, $promotion, $hint);
        }
        if ($chosen['shared'] && $promotion->repeat) {
            // ще одна повна група вже набрана, бракує лише безкоштовної позиції
            $rest = $chosen['units']->count() - $times * $chosen['group'];
            if ($rest >= $chosen['c']['pcs']) {
                $need = $chosen['group'] - $rest;
                $hint = "Додайте ще {$need} " . $this->positions($need) . ' з «' . $this->categoryName($chosen['c']['id'], $posterProducts) . '» — і ще одна буде у подарунок';
            }
        }

        $discount = (int) round($free->sum('price') * $promotion->bonusPercent / 100);

        return new PromoQuote(
            weekday: $day->weekday,
            posterPromotionId: $promotion->id,
            applied: true,
            discount: $discount,
            extraDiscount: $discount,
            freeItems: $this->countBy($free, 'id'),
            hint: $hint,
            involved: $this->countBy($paid, 'poster'),
            result: $this->countBy($free, 'poster'),
        );
    }

    /** Подарунок — окремий товар Poster (один із кількох на вибір клієнта). */
    private function giftProducts(PromoDay $day, PosterPromotion $promotion, array $chosen, int $times, ?int $gift, float $subtotal, array $posterProducts): PromoQuote
    {
        $options = collect($promotion->bonusProductIds())->map(fn (int $id) => $this->giftOption($day, $id, $posterProducts))->values();
        $selected = $options->firstWhere('posterId', $gift) ?? $options->first();
        $giftOptions = $options->count() > 1 ? $options->all() : [];

        if ($times < 1) {
            $names = $options->pluck('name')->implode(' або ');

            return $this->notApplied($day, $promotion, $this->hint($chosen, $posterProducts, "— і {$names} у подарунок"), $giftOptions, $selected['posterId']);
        }
        $count = $times * $promotion->bonusPcs;
        $involved = $chosen['c']['type'] === PosterPromotion::CONDITION_SUM
            ? $chosen['units']
            : $chosen['units']->sortByDesc('price')->take($times * $chosen['c']['pcs']);

        return new PromoQuote(
            weekday: $day->weekday,
            posterPromotionId: $promotion->id,
            applied: true,
            gifts: [[...$selected, 'count' => $count]],
            giftOptions: $giftOptions,
            selectedGift: $selected['posterId'],
            involved: $this->countBy($involved, 'poster'),
            result: [$selected['posterId'] => $count],
        );
    }

    /**
     * Подарунок Poster так, як його показати на сайті: товари сайту з адмінки (набір «Дві піци»)
     * або товар сайту з тим самим poster_id; інакше — лише назва з Poster.
     */
    private function giftOption(PromoDay $day, int $posterId, array $posterProducts): array
    {
        $ids = $day->giftDisplayProducts($posterId);
        $products = $ids
            ? Product::query()->whereIn('id', $ids)->get()->sortBy(fn (Product $p) => array_search($p->id, $ids, true))
            : Product::query()->where('isActive', true)->where('poster_id', $posterId)->limit(1)->get();
        // картка подарунка може бути прихованою (немає в меню) — тож фото віддаємо одразу
        $items = $products->map(fn (Product $p) => [
            'productId' => $p->id,
            'name' => $this->cleanTitle($p->title),
            'price' => (float) $p->price,
            'image' => $p->main_image ? (preg_match('#^https?://#', $p->main_image) ? $p->main_image : asset(ltrim($p->main_image, '/'))) : null,
        ])->values()->all();

        return [
            'posterId' => $posterId,
            'productId' => count($items) === 1 ? $items[0]['productId'] : null,
            'name' => $items ? implode(' + ', array_column($items, 'name')) : ($posterProducts[$posterId]['name'] ?? "Товар #{$posterId}"),
            'price' => $items ? (float) array_sum(array_column($items, 'price')) : (float) ($posterProducts[$posterId]['price'] ?? 0),
            'items' => $items,
        ];
    }

    /** «Піца Карбонара450г» → «Піца Карбонара», як на картках сайту */
    private function cleanTitle(string $title): string
    {
        return trim(preg_replace('/\s*\d+\s*г\.?\s*$/iu', '', trim($title)));
    }

    private function notApplied(PromoDay $day, PosterPromotion $promotion, ?string $hint, array $giftOptions = [], ?int $selected = null): PromoQuote
    {
        return new PromoQuote(
            weekday: $day->weekday,
            posterPromotionId: $promotion->id,
            applied: false,
            giftOptions: $giftOptions,
            selectedGift: $selected,
            hint: $hint,
        );
    }

    /** «Додайте страв ще на 120 грн — …» / «Додайте ще 1 позицію з «Сети» — …» */
    private function hint(array $chosen, array $posterProducts, string $reward, ?int $group = null): ?string
    {
        $c = $chosen['c'];
        if ($c['type'] === PosterPromotion::CONDITION_SUM) {
            $missing = (int) ceil($c['sum'] - $chosen['units']->sum('price'));

            return $missing > 0 ? "Додайте страв ще на {$missing} грн {$reward}" : null;
        }
        $need = ($group ?? $c['pcs']) - $chosen['units']->count();
        if ($need < 1) {
            return null;
        }
        $what = $c['type'] === PosterPromotion::CONDITION_CATEGORY
            ? 'з «' . $this->categoryName($c['id'], $posterProducts) . '»'
            : '«' . ($posterProducts[$c['id']]['name'] ?? 'товару акції') . '»';

        return "Додайте ще {$need} " . $this->positions($need) . " {$what} {$reward}";
    }

    /**
     * Кошик поштучно: [id сайту, id Poster, ціна сайту].
     *
     * @return Collection<int, array{id: int, poster: int, price: float}>
     */
    private function units(array $lines, array $posterProducts): Collection
    {
        $products = Product::query()->whereIn('id', array_keys($lines))->whereNotNull('poster_id')->get()->keyBy('id');
        $units = collect();
        foreach ($lines as $id => $qty) {
            $product = $products->get($id);
            for ($i = 0; $product && $i < (int) $qty; $i++) {
                $units->push(['id' => $product->id, 'poster' => (int) $product->poster_id, 'price' => (float) $product->price]);
            }
        }

        return $units;
    }

    private function matches(array $condition, int $posterId, array $posterProducts): bool
    {
        return match ($condition['type']) {
            PosterPromotion::CONDITION_CATEGORY => ($posterProducts[$posterId]['category_id'] ?? null) === $condition['id'],
            PosterPromotion::CONDITION_PRODUCT => $posterId === $condition['id'],
            default => false,
        };
    }

    private function bonusSharesPool(PosterPromotion $promotion, array $condition): bool
    {
        return $condition['type'] === PosterPromotion::CONDITION_CATEGORY && in_array($condition['id'], $promotion->bonusCategories(), true);
    }

    private function categoryName(int $categoryId, array $posterProducts): string
    {
        foreach ($posterProducts as $p) {
            if ($p['category_id'] === $categoryId && $p['category'] !== '') {
                return $p['category'];
            }
        }

        return 'акції';
    }

    /** @return array<int, int> */
    private function countBy(Collection $units, string $key): array
    {
        return $units->countBy($key)->map(fn ($n) => (int) $n)->all();
    }

    private function positions(int $n): string
    {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($mod10 === 1 && $mod100 !== 11) {
            return 'позицію';
        }

        return $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) ? 'позиції' : 'позицій';
    }

    private function num(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
