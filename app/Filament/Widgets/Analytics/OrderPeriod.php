<?php

namespace App\Filament\Widgets\Analytics;

use Carbon\CarbonImmutable;

/**
 * Період аналітики з фільтрів дашборда + такий самий попередній період для порівняння
 */
final class OrderPeriod
{
    public const PRESETS = [
        'today' => 'Сьогодні',
        'yesterday' => 'Вчора',
        'last_7' => 'Останні 7 днів',
        'last_30' => 'Останні 30 днів',
        'this_month' => 'Цей місяць',
        'last_month' => 'Минулий місяць',
        'this_year' => 'Цей рік',
        'custom' => 'Свій період',
    ];

    public const DEFAULT_PRESET = 'last_7';

    /** Довші періоди групуємо на графіках по місяцях, а не по днях */
    private const DAILY_LIMIT_DAYS = 62;

    private function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly CarbonImmutable $previousFrom,
        public readonly CarbonImmutable $previousTo,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    public static function fromFilters(?array $filters, ?CarbonImmutable $now = null): self
    {
        $now ??= CarbonImmutable::now();
        $today = $now->startOfDay();
        $preset = $filters['period'] ?? self::DEFAULT_PRESET;

        [$from, $to] = match ($preset) {
            'today' => [$today, $today],
            'yesterday' => [$today->subDay(), $today->subDay()],
            'last_30' => [$today->subDays(29), $today],
            'this_month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            'this_year' => [$today->startOfYear(), $today],
            'custom' => self::customRange($filters, $today),
            default => [$today->subDays(6), $today],
        };

        // місячні пресети порівнюємо з тими ж днями попереднього місяця, решту — з попереднім відрізком такої ж довжини
        if (in_array($preset, ['this_month', 'last_month'], true)) {
            $previousFrom = $from->subMonthNoOverflow();
            $previousTo = $to->isSameDay($to->endOfMonth()) ? $previousFrom->endOfMonth()->startOfDay() : $to->subMonthNoOverflow();
        } elseif ($preset === 'this_year') {
            $previousFrom = $from->subYear();
            $previousTo = $to->subYear();
        } else {
            $days = $from->diffInDays($to) + 1;
            $previousTo = $from->subDay();
            $previousFrom = $previousTo->subDays($days - 1);
        }

        // період ще триває — порівнюємо з попереднім до того ж часу доби, а не з повним днем
        $previousEnd = $to->isSameDay($today) ? $previousTo->setTimeFrom($now) : $previousTo->endOfDay();

        return new self($from->startOfDay(), $to->endOfDay(), $previousFrom->startOfDay(), $previousEnd);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function customRange(?array $filters, CarbonImmutable $today): array
    {
        $from = self::parseDate($filters['from'] ?? null);
        $to = self::parseDate($filters['to'] ?? null) ?? $today;
        if (! $from) {
            return [$today->subDays(6), $today];
        }
        if ($to->greaterThan($today)) {
            $to = $today;
        }
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to->startOfDay()) + 1;
    }

    public function isDaily(): bool
    {
        return $this->days() <= self::DAILY_LIMIT_DAYS;
    }

    /**
     * Ключі інтервалів графіка по порядку: Y-m-d по днях або Y-m по місяцях
     *
     * @return array<string, string> ключ => підпис
     */
    public function buckets(): array
    {
        $buckets = [];
        if ($this->isDaily()) {
            for ($d = $this->from; $d->lessThanOrEqualTo($this->to); $d = $d->addDay()) {
                $buckets[$d->format('Y-m-d')] = $d->format('d.m');
            }
        } else {
            for ($d = $this->from->startOfMonth(); $d->lessThanOrEqualTo($this->to); $d = $d->addMonth()) {
                $buckets[$d->format('Y-m')] = $d->format('m.Y');
            }
        }

        return $buckets;
    }

    public function bucketKey(\DateTimeInterface $date): string
    {
        return $date->format($this->isDaily() ? 'Y-m-d' : 'Y-m');
    }

    public function label(): string
    {
        return $this->from->isSameDay($this->to)
            ? $this->from->format('d.m.Y')
            : $this->from->format('d.m.Y') . ' – ' . $this->to->format('d.m.Y');
    }
}
