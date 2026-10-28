<?php

namespace App\Filament\Resources\Blog\VideoCharacters;

use App\Filament\Resources\Blog\VideoCharacters\Pages\CreateVideoCharacter;
use App\Filament\Resources\Blog\VideoCharacters\Pages\EditVideoCharacter;
use App\Filament\Resources\Blog\VideoCharacters\Pages\ListVideoCharacters;
use App\Models\Blog\VideoCharacter;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VideoCharacterResource extends Resource
{
    protected static ?string $model = VideoCharacter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'video-characters';

    protected static ?string $modelLabel = 'персонаж';

    protected static ?string $pluralModelLabel = 'Персонажі';

    protected static string|UnitEnum|null $navigationGroup = 'Відео для блогу';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Персонаж')->schema([
                    TextInput::make('name')->label("Ім'я")->required()->maxLength(255)
                        ->helperText("Під цим ім'ям персонаж з'являється в репліках сценарію"),
                    Textarea::make('description')->label('Зовнішність')->rows(3)
                        ->placeholder('Жінка ~30 років, руде каре, фартух з логотипом, привітна усмішка'),
                    Textarea::make('voice')->label('Голос')->rows(2)
                        ->placeholder('Теплий жіночий голос, говорить швидко й весело'),
                ])->columnSpanFull(),
                Section::make('Референси')
                    ->description('Фото обличчя/образу з різних ракурсів — модель тримає схожість персонажа за ними. 1–3 фото зазвичай достатньо.')
                    ->schema([
                        FileUpload::make('reference_images')->hiddenLabel()->image()->multiple()->reorderable()
                            ->maxFiles(5)->maxSize(10 * 1024)->panelLayout('grid')
                            ->disk('public_root')->directory('blog-video/references')->visibility('public'),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('reference_images')->label('Референси')->disk('public_root')->circular()->stacked()->limit(3),
                TextColumn::make('name')->label("Ім'я")->searchable()->sortable(),
                TextColumn::make('description')->label('Зовнішність')->limit(80),
                TextColumn::make('voice')->label('Голос')->limit(40),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideoCharacters::route('/'),
            'create' => CreateVideoCharacter::route('/create'),
            'edit' => EditVideoCharacter::route('/{record}/edit'),
        ];
    }
}
