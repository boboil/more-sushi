<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrdersByWeekdayChart extends ChartWidget
{
    use InteractsWithPageFilters;

    private const DAYS = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Нд'];

    protected static ?int $sort = 4;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    protected ?string $heading = 'Дні тижня';

    protected ?string $description = 'Середня кількість замовлень за день';

    protected function getData(): array
    {
        $period = OrderPeriod::fromFilters($this->pageFilters);
        $byDay = OrderAnalytics::orders($period->from, $period->to)
            ->countBy(fn (Order $o) => (int) $o->created_at->format('N'));

        // скільки разів кожен день тижня трапився в періоді — щоб рахувати середнє, а не суму
        $occurrences = array_fill_keys(array_keys(self::DAYS), 0);
        for ($d = $period->from; $d->lessThanOrEqualTo($period->to); $d = $d->addDay()) {
            $occurrences[(int) $d->format('N')]++;
        }

        return [
            'datasets' => [[
                'label' => 'Замовлень у середньому',
                'data' => array_map(
                    fn (int $day) => $occurrences[$day] ? round($byDay->get($day, 0) / $occurrences[$day], 1) : 0,
                    array_keys(self::DAYS),
                ),
                'backgroundColor' => '#fb923c',
                'borderRadius' => 4,
            ]],
            'labels' => array_values(self::DAYS),
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true]]];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
