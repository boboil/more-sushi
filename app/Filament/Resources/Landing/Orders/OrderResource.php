<?php

namespace App\Filament\Resources\Landing\Orders;

use App\Filament\Resources\Landing\Orders\Pages\CreateOrder;
use App\Filament\Resources\Landing\Orders\Pages\EditOrder;
use App\Filament\Resources\Landing\Orders\Pages\ListOrders;
use App\Models\Landing\Order;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
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

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'landing-orders';

    protected static ?string $modelLabel = 'замовлення з лендінгу';

    protected static ?string $pluralModelLabel = 'Замовлення з лендінгу';

    protected static string|UnitEnum|null $navigationGroup = 'Лендінг';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Замовлення')->schema([
                    TextInput::make('name')->label('Імʼя')->required(),
                    TextInput::make('phone')->label('Телефон')->tel()->required(),
                    TextInput::make('address')->label('Адреса')->required(),
                    DateTimePicker::make('time')->label('Час')->required(),
                    TextInput::make('sum')->label('Сума замовлення, грн')->numeric()->required(),
                    Toggle::make('self_pickup')->label('Самовивіз'),
                ])->columns(3)->columnSpanFull(),
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
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Імʼя')
                    ->searchable(),
                TextColumn::make('phone')->label('Телефон')->searchable(),
                TextColumn::make('sum')->label('Сума, грн')->numeric(),
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
