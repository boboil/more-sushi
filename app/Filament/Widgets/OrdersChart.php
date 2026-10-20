<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Order;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class OrdersChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected ?string $maxHeight = '280px';

    public ?string $filter = 'count';

    public function getHeading(): string
    {
        return 'Динаміка';
    }

    public function getDescription(): string
    {
        $period = OrderPeriod::fromFilters($this->pageFilters);

        return ($period->isDaily() ? 'По днях' : 'По місяцях') . ', поруч — попередній період';
    }

    protected function getFilters(): ?array
    {
        return [
            'count' => 'Замовлення',
            'revenue' => 'Виручка, грн',
        ];
    }

    protected function getData(): array
    {
        $period = OrderPeriod::fromFilters($this->pageFilters);
        $buckets = $period->buckets();
        $current = $this->values(OrderAnalytics::orders($period->from, $period->to), $period, array_keys($buckets));

        // попередній період накладаємо на ту ж вісь: перший день до першого дня тощо
        $previousPeriod = OrderPeriod::fromFilters(['period' => 'custom', 'from' => $period->previousFrom->toDateString(), 'to' => $period->previousTo->toDateString()], $period->to);
        $previous = $this->values(OrderAnalytics::orders($period->previousFrom, $period->previousTo), $previousPeriod, array_keys($previousPeriod->buckets()));
        // по днях вирівнюємо з початку (у місяцях різна кількість днів), по місяцях — з кінця:
        // попередній період закінчується просто перед поточним, тож його перший місяць може бути неповним
        $previous = $period->isDaily()
            ? array_slice(array_pad($previous, count($buckets), 0), 0, count($buckets))
            : array_slice(array_pad($previous, -count($buckets), 0), -count($buckets));

        return [
            'datasets' => [
                [
                    'label' => $this->filter === 'revenue' ? 'Виручка' : 'Замовлення',
                    'data' => $current,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Попередній період',
                    'data' => $previous,
                    'borderColor' => '#9ca3af',
                    'borderDash' => [5, 5],
                    'pointRadius' => 0,
                    'fill' => false,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_values($buckets),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Order>  $orders
     * @param  list<string>  $keys
     * @return list<float|int>
     */
    private function values($orders, OrderPeriod $period, array $keys): array
    {
        $groups = $orders->groupBy(fn (Order $o) => $period->bucketKey($o->created_at));

        return array_map(
            fn (string $key) => $this->filter === 'revenue'
                ? round((float) ($groups->get($key)?->sum('sum') ?? 0))
                : ($groups->get($key)?->count() ?? 0),
            $keys,
        );
    }

    protected function getOptions(): array
    {
        return ['scales' => ['y' => ['beginAtZero' => true]]];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
