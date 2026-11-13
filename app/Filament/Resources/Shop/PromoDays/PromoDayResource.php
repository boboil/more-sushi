<?php

namespace App\Filament\Resources\Shop\PromoDays;

use App\Filament\Resources\Shop\PromoDays\Pages\EditPromoDay;
use App\Filament\Resources\Shop\PromoDays\Pages\ListPromoDays;
use App\Models\Shop\PromoDay;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PromoDayResource extends Resource
{
    protected static ?string $model = PromoDay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $slug = 'promo-days';

    protected static ?string $modelLabel = 'акція дня';

    protected static ?string $pluralModelLabel = 'Акції дня';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    private const TIME_RULE = 'regex:/^([01]\d|2[0-3]):[0-5]\d$/';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn (?PromoDay $record) => $record ? $record->label() . ' — /promo/' . PromoDay::DAYS[$record->weekday]['slug'] : 'Акція')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_active')->label('Акція діє в цей день')->default(true)->columnSpanFull(),
                            Select::make('offer_type')->label('Механіка')->required()->live()
                                ->options([PromoDay::TYPE_PERCENT => 'Знижка у відсотках', PromoDay::TYPE_GIFT => 'Подарунок / спецпропозиція']),
                            TextInput::make('discount_percent')->label('Знижка, %')->numeric()->minValue(1)->maxValue(90)
                                ->required(fn (Get $get) => $get('offer_type') === PromoDay::TYPE_PERCENT)
                                ->visible(fn (Get $get) => $get('offer_type') === PromoDay::TYPE_PERCENT)
                                ->helperText('Знижка застосовується до товарів акції на всьому сайті в години дії.'),
                            TextInput::make('gift_badge')->label('Бейдж подарунка')->maxLength(20)->placeholder('2+1')
                                ->visible(fn (Get $get) => $get('offer_type') === PromoDay::TYPE_GIFT)
                                ->helperText('Ціни не змінюються, подарунок додає оператор.'),
                            TextInput::make('offer_title')->label('Оффер (під великою цифрою)')->required()->maxLength(80)
                                ->placeholder('на всі філадельфії')->columnSpanFull(),
                            TextInput::make('offer_short')->label('Коротко для стрічки днів')->required()->maxLength(24)
                                ->placeholder('-30% філадельфії'),
                            Grid::make(2)->schema([
                                TextInput::make('active_from')->label('Діє з')->required()->rule(self::TIME_RULE)->placeholder('11:00'),
                                TextInput::make('active_to')->label('Діє до')->required()->rule(self::TIME_RULE)->placeholder('23:00'),
                            ]),
                            Textarea::make('terms')->label('Умови акції')->rows(3)->maxLength(500)->columnSpanFull()
                                ->helperText('Години дії додаються автоматично на початку тексту.'),
                            FileUpload::make('hero_image')->label('Фото для першого екрана')->image()
                                ->disk('public_root')->directory('images/promo-days')->visibility('public')
                                ->helperText('Квадратне. Якщо порожньо — фото першого товару акції.')->columnSpanFull(),
                            Select::make('products')->label('Товари акції (1–8)')
                                ->relationship('products', 'title', fn ($query) => $query->where('isActive', true))
                                ->multiple()->preload()->searchable()->maxItems(8)
                                ->columnSpanFull(),
                        ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('weekday')
            ->paginated(false)
            ->columns([
                TextColumn::make('weekday')->label('День')->formatStateUsing(fn (int $state) => PromoDay::DAYS[$state]['label'] ?? $state),
                TextColumn::make('offer_short')->label('Оффер'),
                TextColumn::make('hours')->label('Години')->state(fn (PromoDay $record) => "{$record->active_from}–{$record->active_to}"),
                TextColumn::make('products_count')->label('Товарів')->counts('products'),
                ToggleColumn::make('is_active')->label('Діє'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPromoDays::route('/'),
            'edit' => EditPromoDay::route('/{record}/edit'),
        ];
    }
}
