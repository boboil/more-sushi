<?php

namespace App\Filament\Widgets\Analytics;

use App\Models\Shop\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Вибірки по замовленнях із сайту (shop_order) для віджетів дашборда.
 * Агрегуємо в PHP: за рік це ~10 тис. рядків, а код однаково працює на MySQL і SQLite (тести).
 */
final class OrderAnalytics
{
    public const DELIVERY_COURIER = 'курʼєр';

    public const DELIVERY_PICKUP = 'самовивiz';

    /**
     * @return Collection<int, Order>
     */
    public static function orders(CarbonImmutable $from, CarbonImmutable $to, ?string $payment = null): Collection
    {
        return Order::query()
            ->whereBetween('created_at', [$from, $to])
            ->when($payment, fn ($q) => $q->where('online_payment', $payment))
            ->get(['id', 'created_at', 'sum', 'customer_phone', 'customer_delivery_type', 'online_payment', 'attribution']);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{count: int, revenue: float, average: float, customers: int, newCustomers: int}
     */
    public static function summary(Collection $orders, CarbonImmutable $from): array
    {
        $count = $orders->count();
        $revenue = (float) $orders->sum('sum');
        $phones = $orders->map(fn (Order $o) => self::phoneKey($o->customer_phone))->filter()->unique();

        // новий клієнт — той, у кого до початку періоду не було жодного замовлення з цього номера
        $known = $phones->isEmpty() ? collect() : Order::query()
            ->where('created_at', '<', $from)
            ->pluck('customer_phone')
            ->map(fn ($phone) => self::phoneKey($phone))
            ->filter()
            ->flip();

        return [
            'count' => $count,
            'revenue' => $revenue,
            'average' => $count ? $revenue / $count : 0.0,
            'customers' => $phones->count(),
            'newCustomers' => $phones->reject(fn (string $p) => $known->has($p))->count(),
        ];
    }

    /**
     * Групує замовлення за підписом (канал, кампанія, місяць) у рядки для таблиць.
     * Замовлення, для яких $label повертає null, пропускаються.
     *
     * @param  Collection<int, Order>  $orders
     * @param  callable(Order): ?string  $label
     * @return array<string, array{key: string, label: string, orders: int, revenue: float, average: float, share: float}>
     */
    public static function breakdown(Collection $orders, callable $label): array
    {
        $total = $orders->count();
        $rows = [];
        foreach ($orders->groupBy(fn (Order $o) => $label($o) ?? '')->forget('') as $name => $group) {
            $count = $group->count();
            $revenue = (float) $group->sum('sum');
            $rows[$name] = [
                'key' => (string) $name,
                'label' => (string) $name,
                'orders' => $count,
                'revenue' => $revenue,
                'average' => $count ? $revenue / $count : 0.0,
                'share' => $total ? $count / $total * 100 : 0.0,
            ];
        }
        uasort($rows, fn (array $a, array $b) => [$b['orders'], $b['revenue']] <=> [$a['orders'], $a['revenue']]);

        return $rows;
    }

    /**
     * Один клієнт — один номер, хоч би як його ввели: "+380 99…", "099…", "99…"
     */
    public static function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 9 ? substr($digits, -9) : null;
    }

    public static function percentChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return ($current - $previous) / $previous * 100;
    }

    public static function money(float $value): string
    {
        return number_format($value, 0, ',', ' ') . ' грн';
    }
}
