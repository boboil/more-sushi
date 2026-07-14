<?php

namespace App\Filament\Resources\Shop\Products;

use App\Filament\Resources\Shop\Products\Pages\CreateProduct;
use App\Filament\Resources\Shop\Products\Pages\EditProduct;
use App\Filament\Resources\Shop\Products\Pages\ListProducts;
use App\Models\Shop\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'products';

    protected static ?string $modelLabel = 'товар';

    protected static ?string $pluralModelLabel = 'Товари';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основна інформація')->schema([
                    Grid::make(3)->schema([
                        TextInput::make('title')->label('Назва')->required()->maxLength(255)->columnSpan(2),
                        FileUpload::make('main_image')->label('Головне зображення')->image()->disk('public_root')->directory('images/uploads')->visibility('public')->required(),
                        TextInput::make('price')->label('Ціна')->numeric()->required(),
                        TextInput::make('discount')->label('Знижка')->numeric(),
                        TextInput::make('weight')->label('Вага, г')->numeric()->required(),
                        TextInput::make('count')->label('Кількість, шт.')->numeric()->required(),
                        Select::make('category')->label('Категорії')->relationship('category', 'title')->multiple()->preload()->searchable(),
                    ]),
                    FileUpload::make('images')->label('Зображення товару')->image()->multiple()->disk('public_root')->directory('images/uploads')->visibility('public')->columnSpanFull(),
                    RichEditor::make('consist')->label('Склад')->columnSpanFull(),
                    RichEditor::make('description')->label('Опис')->columnSpanFull(),
                ])->columnSpanFull(),
                Section::make('Відображення')->schema([
                    Toggle::make('stock')->label('Акція'),
                    Toggle::make('latest')->label('Новинка'),
                    Toggle::make('isRelated')->label('Супутній товар'),
                    Toggle::make('isActive')->label('Відображати на сайті'),
                    Toggle::make('for_landing')->label('Відображати на лендінгу'),
                ])->columns(5)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Назва')
                    ->searchable(),
                TextColumn::make('price')->label('Ціна')->numeric(),
                TextColumn::make('discount')->label('Знижка')->numeric(),
                ImageColumn::make('main_image')->label('Зображення')->disk('public_root'),
                ToggleColumn::make('stock')->label('Акція'),
                ToggleColumn::make('latest')->label('Новинка'),
                ToggleColumn::make('isRelated')->label('Супутній'),
                ToggleColumn::make('isActive')->label('На сайті'),
                ToggleColumn::make('for_landing')->label('На лендінгу'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deleteSelected')
                        ->label('Видалити обрані')
                        ->icon(Heroicon::Trash)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->delete())
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
