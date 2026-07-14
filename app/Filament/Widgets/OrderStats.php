<?php

namespace App\Filament\Widgets;

use App\Models\Shop\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $orders = Order::query()->whereDate('created_at', today())->get();

        return [
            Stat::make('Замовлень сьогодні', $orders->count())
                ->icon('heroicon-o-shopping-bag'),
            Stat::make('Сума сьогодні', number_format((float) $orders->sum('sum'), 2, ',', ' ') . ' грн')
                ->icon('heroicon-o-banknotes'),
        ];
    }
}
