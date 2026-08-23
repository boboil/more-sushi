<?php

namespace App\Filament\Resources\Shop\HeroSlides;

use App\Filament\Resources\Shop\HeroSlides\Pages\CreateHeroSlide;
use App\Filament\Resources\Shop\HeroSlides\Pages\EditHeroSlide;
use App\Filament\Resources\Shop\HeroSlides\Pages\ListHeroSlides;
use App\Models\Shop\HeroSlide;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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

class HeroSlideResource extends Resource
{
    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $recordTitleAttribute = 'button_text';

    protected static ?string $slug = 'hero-slides';

    protected static ?string $modelLabel = 'слайд';

    protected static ?string $pluralModelLabel = 'Слайдер на головній';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Слайд')->schema([
                    FileUpload::make('image')->label('Зображення')->image()->required()
                        ->disk('public_root')->directory('images/hero-slides')->visibility('public'),
                    Grid::make(2)->schema([
                        TextInput::make('button_text')->label('Текст кнопки')->default('Купити')->required()->maxLength(255),
                        TextInput::make('button_link')->label('Посилання кнопки')->required()->maxLength(255)
                            ->helperText('Наприклад: /catalog або /catalog?filter=roli'),
                        TextInput::make('sort_order')->label('Порядок сортування')->numeric()->default(0)->required(),
                        Toggle::make('enable')->label('Активний')->default(true),
                    ]),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('button_text')
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Зображення')->disk('public_root'),
                TextColumn::make('button_text')->label('Текст кнопки'),
                TextColumn::make('button_link')->label('Посилання'),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                ToggleColumn::make('enable')->label('Активний'),
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
            'index' => ListHeroSlides::route('/'),
            'create' => CreateHeroSlide::route('/create'),
            'edit' => EditHeroSlide::route('/{record}/edit'),
        ];
    }
}
