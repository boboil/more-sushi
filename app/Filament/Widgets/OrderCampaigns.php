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

class OrderCampaigns extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 9;

    protected static ?string $heading = 'Рекламні кампанії';

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                $period = OrderPeriod::fromFilters($this->pageFilters);

                return OrderAnalytics::breakdown(
                    OrderAnalytics::orders($period->from, $period->to, $period->payment),
                    fn (Order $o) => OrderAttribution::campaign($o->attribution),
                );
            })
            ->description('Замовлення з посилань з utm_campaign. Частка — від усіх замовлень за період')
            ->emptyStateHeading('Замовлень з кампаній ще немає')
            ->emptyStateDescription('Додавайте до реклами ?utm_source=…&utm_medium=cpc&utm_campaign=…')
            ->columns(BreakdownTable::columns('Джерело · кампанія'))
            ->paginated(false);
    }
}
