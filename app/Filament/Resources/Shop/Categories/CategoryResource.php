<?php

namespace App\Filament\Resources\Shop\Categories;

use App\Filament\Resources\Shop\Categories\Pages\CreateCategory;
use App\Filament\Resources\Shop\Categories\Pages\EditCategory;
use App\Filament\Resources\Shop\Categories\Pages\ListCategories;
use App\Models\Shop\Category;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'categories';

    protected static ?string $modelLabel = 'категорія';

    protected static ?string $pluralModelLabel = 'Категорії';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Категорія')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')->label('Назва')->required()->maxLength(255),
                        Toggle::make('enable')->label('Активна'),
                        TextInput::make('meta_title')->label('Мета-заголовок'),
                        TextInput::make('meta_description')->label('Мета-опис'),
                    ]),
                    FileUpload::make('image')->label('Зображення')->image()->disk('public_root')->directory('images/uploads')->visibility('public'),
                    RichEditor::make('description')->label('Опис категорії')->columnSpanFull(),
                ])->columnSpanFull(),
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
                ImageColumn::make('image')->label('Зображення')->disk('public_root'),
                ToggleColumn::make('enable')->label('Активна'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
