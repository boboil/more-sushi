<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrdersByHourChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    protected ?string $heading = 'Замовлення за годинами';

    protected ?string $description = 'Коли клієнти оформлюють замовлення на сайті';

    protected function getData(): array
    {
        $period = OrderPeriod::fromFilters($this->pageFilters);
        $byHour = OrderAnalytics::orders($period->from, $period->to)
            ->countBy(fn (Order $o) => (int) $o->created_at->format('G'));

        // показуємо робочий день з запасом; нічні замовлення (якщо є) теж не губимо
        $hours = range(min(9, $byHour->keys()->min() ?? 9), max(21, $byHour->keys()->max() ?? 21));

        return [
            'datasets' => [[
                'label' => 'Замовлення',
                'data' => array_map(fn (int $h) => $byHour->get($h, 0), $hours),
                'backgroundColor' => '#f59e0b',
                'borderRadius' => 4,
            ]],
            'labels' => array_map(fn (int $h) => sprintf('%02d:00', $h), $hours),
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
