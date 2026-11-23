<?php

namespace App\Services\Promo;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Акція Poster (clients.getPromotion) у зручному вигляді.
 * Години акції з Poster не враховуємо: сайт приймає замовлення лише в години «Акції дня».
 *
 * @see https://dev.joinposter.com/docs/v3/web/clients/getPromotion
 */
class PosterPromotion
{
    /** Точка, на яку йдуть замовлення сайту (PosterAuthController::createIncomingOrder). */
    public const SPOT_ID = '4';

    public const CONDITION_SUM = 0;

    public const CONDITION_CATEGORY = 1;

    public const CONDITION_PRODUCT = 2;

    public const RESULT_BONUS = 1;

    public const RESULT_PERCENT = 3;

    /**
     * @param  array<int, bool>  $weekDays  1 = понеділок … 7 = неділя
     * @param  list<array{type: int, id: int, pcs: int, sum: float}>  $conditions  sum — у гривнях
     * @param  list<array{type: int, id: int}>  $bonusProducts
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $weekDays,
        public readonly ?Carbon $dateStart,
        public readonly ?Carbon $dateEnd,
        public readonly array $conditions,
        public readonly bool $allConditions,
        public readonly bool $repeat,
        public readonly int $resultType,
        public readonly float $discountPercent,
        public readonly array $bonusProducts,
        public readonly int $bonusPcs,
        public readonly ?float $bonusPercent,
        public readonly bool $onSpot,
    ) {}

    public static function fromPoster(array $raw): self
    {
        $params = $raw['params'] ?? [];
        $weekDays = [];
        foreach (array_values($params['week_days'] ?? []) as $i => $flag) {
            $weekDays[$i + 1] = (string) $flag === '1';
        }

        return new self(
            id: (int) $raw['promotion_id'],
            name: trim((string) ($raw['name'] ?? '')),
            weekDays: $weekDays,
            dateStart: self::date($raw['date_start'] ?? null),
            dateEnd: self::date($raw['date_end'] ?? null),
            conditions: array_map(fn (array $c) => [
                'type' => (int) ($c['type'] ?? 0),
                'id' => (int) ($c['id'] ?? 0),
                'pcs' => max(1, (int) ($c['pcs'] ?? 1)),
                'sum' => ((int) ($c['sum'] ?? 0)) / 100,
            ], $params['conditions'] ?? []),
            allConditions: ($params['conditions_rule'] ?? 'or') === 'and',
            // 1 — один раз у чеку, 2 — багато разів у чеку (3 — накопичення між чеками сайт не підтримує)
            repeat: (string) ($params['accumulation_type'] ?? '1') === '2',
            resultType: (int) ($params['result_type'] ?? 0),
            discountPercent: (float) ($params['discount_value'] ?? 0),
            bonusProducts: array_map(fn (array $b) => ['type' => (int) $b['type'], 'id' => (int) $b['id']], $params['bonus_products'] ?? []),
            bonusPcs: max(1, (int) ($params['bonus_products_pcs'] ?? 1)),
            // 1 — відсоток знижки на бонусний товар; інші варіанти сайт не рахує
            bonusPercent: (string) ($params['bonus_products_condition_type'] ?? '') === '1' ? (float) $params['bonus_products_condition_value'] : null,
            onSpot: ! isset($params['available_for_spots']) || (bool) ($params['available_for_spots'][self::SPOT_ID] ?? false),
        );
    }

    /** Чи діє акція в цю дату на точці сайту (день тижня й період дії; години — ні). */
    public function activeOn(CarbonInterface $now, bool $ignoreWeekday = false): bool
    {
        if (! $this->onSpot || (! $ignoreWeekday && ! ($this->weekDays[$now->isoWeekday()] ?? false))) {
            return false;
        }
        if ($this->dateStart && $now->lt($this->dateStart)) {
            return false;
        }

        return ! $this->dateEnd || $now->lte($this->dateEnd);
    }

    /** Механіки, які сайт уміє порахувати сам. Решту оператор застосовує на касі вручну. */
    public function supported(): bool
    {
        if ($this->conditions === []) {
            return false;
        }
        foreach ($this->conditions as $c) {
            if (! in_array($c['type'], [self::CONDITION_SUM, self::CONDITION_CATEGORY, self::CONDITION_PRODUCT], true)) {
                return false;
            }
        }

        return match ($this->resultType) {
            self::RESULT_PERCENT => $this->discountPercent > 0 && $this->discountPercent <= 100,
            // подарунок — або товари Poster (безкоштовно), або товари з кошика певної категорії
            self::RESULT_BONUS => $this->bonusPercent !== null && (
                ($this->bonusCategories() !== [] && $this->bonusProductIds() === [] && $this->bonusPercent > 0)
                || ($this->bonusProductIds() !== [] && $this->bonusCategories() === [] && $this->bonusPercent == 100)
            ),
            default => false,
        };
    }

    /** @return list<int> категорії Poster, з яких подарунок береться з кошика */
    public function bonusCategories(): array
    {
        return collect($this->bonusProducts)->where('type', self::CONDITION_CATEGORY)->pluck('id')->values()->all();
    }

    /** @return list<int> товари Poster, які додаються в подарунок */
    public function bonusProductIds(): array
    {
        return collect($this->bonusProducts)->where('type', self::CONDITION_PRODUCT)->pluck('id')->values()->all();
    }

    private static function date(?string $value): ?Carbon
    {
        if (! $value || str_starts_with($value, '0000')) {
            return null;
        }

        return Carbon::parse($value, config('app.timezone'));
    }
}
