<?php

namespace App\Filament\Resources\Shop\Orders;

use App\Filament\Resources\Shop\Orders\Pages\CreateOrder;
use App\Filament\Resources\Shop\Orders\Pages\EditOrder;
use App\Filament\Resources\Shop\Orders\Pages\ListOrders;
use App\Models\Shop\Order;
use App\Services\Statistics\OrderAttribution;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'customer_name';

    protected static ?string $slug = 'orders';

    protected static ?string $modelLabel = 'замовлення';

    protected static ?string $pluralModelLabel = 'Замовлення';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Клієнт і доставка')->schema([
                    Grid::make(3)->schema([
                        TextInput::make('customer_name')->label('Імʼя')->required(),
                        TextInput::make('customer_phone')->label('Телефон')->tel()->required(),
                        TextInput::make('customer_delivery_type')->label('Доставка')->required(),
                        TextInput::make('online_payment')->label('Оплата')->required(),
                        TextInput::make('customer_street')->label('Вулиця')->required(),
                        TextInput::make('customer_building')->label('Будинок')->required(),
                        TextInput::make('sticks_standard')->label('Стандартні палички')->numeric()->required(),
                        TextInput::make('sticks_educational')->label('Учбові палички')->numeric()->required(),
                        DateTimePicker::make('time')->label('Час')->required(),
                        Toggle::make('is_as_soon_as_possible')->label('Якомога раніше'),
                        TextInput::make('sum')->label('Сума замовлення, грн')->numeric()->required(),
                    ]),
                ])->columnSpanFull(),
                Section::make('Рекламна кампанія')
                    ->description('Звідки прийшов клієнт: UTM-мітки посилання або сайт, з якого перейшов.')
                    ->schema([
                        Grid::make(3)->schema([
                            self::attributionEntry('channel', 'Канал', fn (?array $a) => OrderAttribution::channel($a)),
                            self::attributionEntry('campaign', 'Кампанія (utm_campaign)', fn (?array $a) => $a['campaign'] ?? null),
                            self::attributionEntry('content', 'Оголошення (utm_content)', fn (?array $a) => $a['content'] ?? null),
                            self::attributionEntry('term', 'Ключ / аудиторія (utm_term)', fn (?array $a) => $a['term'] ?? null),
                            self::attributionEntry('click', 'Клік з реклами', fn (?array $a) => $a['click_id_type'] ?? null),
                            self::attributionEntry('touched', 'Перехід', fn (?array $a) => $a['touched_at'] ?? null),
                            self::attributionEntry('referrer', 'Звідки перейшов', fn (?array $a) => $a['referrer'] ?? null),
                            self::attributionEntry('landing', 'Сторінка входу', fn (?array $a) => $a['landing_page'] ?? null)->columnSpan(2),
                        ]),
                    ])
                    ->collapsible()
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Section::make('Товари')->schema([
                    Repeater::make('order_products')->label('Товари')->schema([
                        Select::make('product_id')->label('Товар')->options(fn () => \App\Models\Shop\Product::query()->pluck('title', 'id'))->searchable()->required()->distinct(),
                        TextInput::make('quantity')->label('Кількість')->numeric()->minValue(1)->default(1)->required(),
                    ])->columns(2)->addActionLabel('Додати товар'),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('customer_name')
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Імʼя')
                    ->searchable(),
                TextColumn::make('customer_phone')->label('Телефон')->searchable(),
                TextColumn::make('customer_delivery_type')->label('Доставка'),
                TextColumn::make('online_payment')->label('Оплата'),
                TextColumn::make('sum')->label('Сума, грн')->numeric(),
                TextColumn::make('attribution_channel')
                    ->label('Канал')
                    ->state(fn (Order $record): string => OrderAttribution::channel($record->attribution))
                    ->badge()
                    ->color(fn (string $state): string => in_array($state, [OrderAttribution::UNTRACKED, OrderAttribution::DIRECT], true) ? 'gray' : 'info')
                    ->toggleable(),
                TextColumn::make('attribution_campaign')
                    ->label('Кампанія')
                    ->state(fn (Order $record): ?string => $record->attribution['campaign'] ?? null)
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('time')->label('Дата замовлення')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([])
            ->defaultSort('time', 'desc');
    }

    /**
     * Поле з shop_order.attribution лише для перегляду.
     */
    private static function attributionEntry(string $name, string $label, \Closure $value): TextEntry
    {
        return TextEntry::make("attribution_{$name}")
            ->label($label)
            ->state(fn (?Order $record) => $value($record?->attribution))
            ->placeholder('—');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
