<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\BreakdownTable;
use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Order;
use App\Services\Statistics\OrderAttribution;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

class OrderSources extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 8;

    protected static ?string $heading = 'Джерела замовлень';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $period = OrderPeriod::fromFilters($this->pageFilters);

                return OrderAnalytics::breakdown(
                    OrderAnalytics::orders($period->from, $period->to, $period->payment),
                    fn (Order $o) => OrderAttribution::channel($o->attribution),
                );
            })
            ->description('Звідки прийшов клієнт: UTM-мітка, пошуковик чи інший сайт. «Без міток» — замовлення до 11.11.2026 або без даних браузера')
            ->columns(BreakdownTable::columns('Канал'))
            ->paginated(false);
    }
}
