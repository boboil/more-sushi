<?php

namespace App\Services\Salary;

use App\Http\Controllers\Utils\PosterAuthController;
use App\Models\SalaryDay;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Каса дня = виторг (revenue) з Poster dash.getCategoriesSales по категоріях із CATEGORIES.
 * Poster віддає копійки, у salary_days пишемо гривні.
 */
class PosterCashSync
{
    /** Категорії Poster, виторг яких іде в касу для зарплати (порівняння без регістру і пробілів по краях). */
    public const CATEGORIES = [
        'Роли ОПТ',
        'Сети',
        'Піца',
        'Сети ОПТ',
        'ПРЕМІУМ Роли та Сети',
        'піца Заморожена',
        'Роли',
        'Холодні напої',
        'Комбо меню',
        'Десерти',
        'Опт Преміум',
    ];

    /** Скільки годин після кінця дня ще перечитувати касу (зміна може закриватись після півночі). */
    private const FINAL_AFTER_HOURS = 6;

    public function __construct(private readonly SalaryPool $pool)
    {
    }

    /** Виторг за день у гривнях по потрібних категоріях. */
    public function revenueFor(CarbonInterface $date): float
    {
        $ymd = $date->format('Ymd');
        $response = app(PosterAuthController::class)->getFinanceReport($ymd, $ymd);

        if (! is_array($response) || ! isset($response['response']) || ! is_array($response['response'])) {
            throw new \RuntimeException('Poster не повернув продажі за ' . $date->toDateString() . ': ' . mb_substr(json_encode($response, JSON_UNESCAPED_UNICODE), 0, 300));
        }

        $wanted = array_map(fn (string $name) => $this->normalize($name), self::CATEGORIES);
        $kopecks = 0;
        foreach ($response['response'] as $row) {
            if (in_array($this->normalize((string) ($row['category_name'] ?? '')), $wanted, true)) {
                $kopecks += (int) ($row['revenue'] ?? 0);
            }
        }

        return round($kopecks / 100, 2);
    }

    public function syncDay(CarbonInterface $date): float
    {
        $cash = $this->revenueFor($date);
        $this->pool->setCash($date, $cash, null);
        SalaryDay::query()->whereDate('date', $date->toDateString())->update(['synced_at' => now()]);

        return $cash;
    }

    /**
     * Підтягнути касу місяця до сьогодні.
     * $force = false — лише дні без каси або ще не «закриті» (сьогодні, вчора до ранку).
     *
     * @return array{synced: int, failed: list<string>}
     */
    public function syncMonth(CarbonInterface $month, bool $force = false): array
    {
        $start = CarbonImmutable::parse($month->toDateString())->startOfMonth();
        $end = $start->endOfMonth()->min(CarbonImmutable::today());
        if ($end->lessThan($start)) {
            return ['synced' => 0, 'failed' => []];
        }

        $existing = SalaryDay::query()
            ->whereDate('date', '>=', $start->toDateString())->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->keyBy(fn (SalaryDay $d) => $d->date->toDateString());

        $synced = 0;
        $failed = [];
        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            if (! $force && $this->isFinal($date, $existing->get($date->toDateString()))) {
                continue;
            }
            try {
                $this->syncDay($date);
                $synced++;
            } catch (Throwable $e) {
                Log::warning('Salary cash sync failed', ['date' => $date->toDateString(), 'error' => $e->getMessage()]);
                $failed[] = $date->toDateString();
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    private function isFinal(CarbonImmutable $date, ?SalaryDay $day): bool
    {
        return $day?->synced_at !== null
            && $day->synced_at->greaterThanOrEqualTo($date->addDay()->addHours(self::FINAL_AFTER_HOURS));
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name)));
    }
}
