<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrderTypesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '260px';

    protected ?string $heading = 'Отримання та оплата';

    protected function getData(): array
    {
        $period = OrderPeriod::fromFilters($this->pageFilters);
        $orders = OrderAnalytics::orders($period->from, $period->to);

        $segments = [
            'Курʼєр · готівка' => fn (Order $o) => $o->customer_delivery_type === OrderAnalytics::DELIVERY_COURIER && $o->online_payment !== 'термінал',
            'Курʼєр · термінал' => fn (Order $o) => $o->customer_delivery_type === OrderAnalytics::DELIVERY_COURIER && $o->online_payment === 'термінал',
            'Самовивіз · готівка' => fn (Order $o) => $o->customer_delivery_type !== OrderAnalytics::DELIVERY_COURIER && $o->online_payment !== 'термінал',
            'Самовивіз · термінал' => fn (Order $o) => $o->customer_delivery_type !== OrderAnalytics::DELIVERY_COURIER && $o->online_payment === 'термінал',
        ];

        return [
            'datasets' => [[
                'data' => array_map(fn (callable $match) => $orders->filter($match)->count(), array_values($segments)),
                'backgroundColor' => ['#f59e0b', '#fcd34d', '#0ea5e9', '#7dd3fc'],
            ]],
            'labels' => array_keys($segments),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
