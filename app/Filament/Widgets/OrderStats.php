<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Order;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;

class OrderStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return 'Замовлення з сайту';
    }

    protected function getDescription(): ?string
    {
        return OrderPeriod::fromFilters($this->pageFilters)->label() . ' · відсотки — зміна до попереднього такого ж періоду';
    }

    protected function getStats(): array
    {
        $period = OrderPeriod::fromFilters($this->pageFilters);
        $orders = OrderAnalytics::orders($period->from, $period->to);
        $now = OrderAnalytics::summary($orders, $period->from);
        $before = OrderAnalytics::summary(OrderAnalytics::orders($period->previousFrom, $period->previousTo), $period->previousFrom);
        [$countByBucket, $revenueByBucket] = $this->series($orders, $period);

        return [
            $this->stat('Замовлень', number_format($now['count'], 0, ',', ' '), $now['count'], $before['count'])
                ->icon(Heroicon::OutlinedShoppingBag)
                ->chart($countByBucket),
            $this->stat('Виручка', OrderAnalytics::money($now['revenue']), $now['revenue'], $before['revenue'])
                ->icon(Heroicon::OutlinedBanknotes)
                ->chart($revenueByBucket),
            $this->stat('Середній чек', OrderAnalytics::money($now['average']), $now['average'], $before['average'])
                ->icon(Heroicon::OutlinedReceiptPercent),
            $this->stat('Клієнтів', number_format($now['customers'], 0, ',', ' '), $now['customers'], $before['customers'])
                ->icon(Heroicon::OutlinedUsers),
            $this->stat('Нових клієнтів', number_format($now['newCustomers'], 0, ',', ' '), $now['newCustomers'], $before['newCustomers'])
                ->icon(Heroicon::OutlinedUserPlus),
            Stat::make('Доставка / самовивіз', $this->deliveryShare($orders))
                ->icon(Heroicon::OutlinedTruck)
                ->description('частка замовлень'),
        ];
    }

    private function stat(string $label, string $value, float $current, float $previous): Stat
    {
        $change = OrderAnalytics::percentChange($current, $previous);
        $stat = Stat::make($label, $value);

        if ($change === null) {
            return $stat->description('немає даних для порівняння');
        }

        $up = $change >= 0;

        return $stat
            ->description(($up ? '+' : '') . number_format($change, 1, ',', ' ') . '%')
            ->descriptionIcon($up ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
            ->color($up ? 'success' : 'danger');
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{0: list<int>, 1: list<float>}
     */
    private function series(Collection $orders, OrderPeriod $period): array
    {
        $groups = $orders->groupBy(fn (Order $o) => $period->bucketKey($o->created_at));
        $counts = [];
        $revenue = [];
        foreach (array_keys($period->buckets()) as $key) {
            $counts[] = $groups->get($key)?->count() ?? 0;
            $revenue[] = (float) ($groups->get($key)?->sum('sum') ?? 0);
        }

        return [$counts, $revenue];
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function deliveryShare(Collection $orders): string
    {
        if ($orders->isEmpty()) {
            return '—';
        }
        $courier = $orders->where('customer_delivery_type', OrderAnalytics::DELIVERY_COURIER)->count();
        $share = (int) round($courier / $orders->count() * 100);

        return $share . '% / ' . (100 - $share) . '%';
    }
}
