<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Analytics\OrderAnalytics;
use App\Filament\Widgets\Analytics\OrderPeriod;
use App\Models\Shop\Product;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopProducts extends TableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 6;

    protected static ?string $heading = 'Топ товарів';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->query())
            ->description('Скільки штук замовили за період. Виручка — за поточною ціною товару, без знижок і доставки')
            ->columns([
                ImageColumn::make('main_image')->label('')->disk('public_root')->imageSize(36),
                TextColumn::make('title')->label('Товар')->wrap(),
                TextColumn::make('quantity')->label('Штук')->numeric()->sortable(),
                TextColumn::make('revenue')->label('Виручка ≈')->sortable()
                    ->formatStateUsing(fn ($state) => OrderAnalytics::money((float) $state)),
                TextColumn::make('orders_count')->label('Замовлень')->numeric(),
            ])
            ->defaultSort('quantity', 'desc')
            ->defaultKeySort(false)
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    private function query(): Builder
    {
        $period = OrderPeriod::fromFilters($this->pageFilters);

        return Product::query()
            ->select('product_shop.id', 'product_shop.title', 'product_shop.main_image')
            ->selectRaw('SUM(shop_order_shop_product.shop_product_quantity) as quantity')
            ->selectRaw('SUM(shop_order_shop_product.shop_product_quantity * product_shop.price) as revenue')
            ->selectRaw('COUNT(DISTINCT shop_order.id) as orders_count')
            ->join('shop_order_shop_product', 'shop_order_shop_product.shop_product_id', '=', 'product_shop.id')
            ->join('shop_order', 'shop_order.id', '=', 'shop_order_shop_product.shop_order_id')
            ->whereBetween('shop_order.created_at', [$period->from, $period->to])
            ->when($period->payment, fn (Builder $q) => $q->where('shop_order.online_payment', $period->payment))
            ->groupBy('product_shop.id', 'product_shop.title', 'product_shop.main_image', 'product_shop.price');
    }
}
