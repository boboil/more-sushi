<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\BreakdownTable;
use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Order;
use Carbon\CarbonImmutable;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;

/**
 * Останні 12 місяців (включно з поточним) незалежно від вибраного періоду; фільтр оплати враховується.
 */
class OrdersByMonth extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 7;

    protected static ?string $heading = 'По місяцях за рік';

    private const MONTHS = ['Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'];

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->rows())
            ->description('Останні 12 місяців, поточний — неповний. Частка — від замовлень за 12 місяців')
            ->columns(BreakdownTable::columns('Місяць'))
            ->paginated(false);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function rows(): array
    {
        $payment = OrderPeriod::fromFilters($this->pageFilters)->payment;
        $to = CarbonImmutable::now()->endOfDay();
        $from = $to->startOfMonth()->subMonths(11)->startOfDay();

        $rows = OrderAnalytics::breakdown(
            OrderAnalytics::orders($from, $to, $payment),
            fn (Order $o) => $o->created_at->format('Y-m'),
        );

        // усі 12 місяців, від нового до старого, навіть без замовлень
        $months = [];
        for ($m = $to->startOfMonth(); $m->greaterThanOrEqualTo($from); $m = $m->subMonth()) {
            $key = $m->format('Y-m');
            $months[$key] = [
                ...($rows[$key] ?? ['key' => $key, 'orders' => 0, 'revenue' => 0.0, 'average' => 0.0, 'share' => 0.0]),
                'label' => self::MONTHS[$m->month - 1] . ' ' . $m->year,
            ];
        }

        return $months;
    }
}
