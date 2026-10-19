<?php

namespace App\Filament\Resources\Shop\Tags;

use App\Filament\Resources\Shop\Tags\Pages\CreateTag;
use App\Filament\Resources\Shop\Tags\Pages\EditTag;
use App\Filament\Resources\Shop\Tags\Pages\ListTags;
use App\Models\Shop\Tag;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'tags';

    protected static ?string $modelLabel = 'фільтр';

    protected static ?string $pluralModelLabel = 'Фільтри';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Фільтр')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')->label('Назва')->required()->maxLength(255)
                            ->helperText('Так фільтр виглядає на сайті, напр. «Гостре»'),
                        TextInput::make('slug')->label('Код для посилання')->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Латиницею, напр. spicy → /menu?f=spicy. Якщо порожньо — згенерується з назви'),
                        TextInput::make('sort_order')->label('Порядок сортування')->numeric()->default(0)->required(),
                        Toggle::make('enable')->label('Показувати на сайті')->default(true),
                    ]),
                    Select::make('products')->label('Товари з цим фільтром')
                        ->relationship('products', 'title')
                        ->multiple()->preload()->searchable()
                        ->columnSpanFull(),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label('Назва')->searchable(),
                TextColumn::make('slug')->label('Код'),
                TextColumn::make('products_count')->label('Товарів')->counts('products')->sortable(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                ToggleColumn::make('enable')->label('На сайті'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
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
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
