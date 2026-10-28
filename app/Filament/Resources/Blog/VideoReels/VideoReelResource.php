<?php

namespace App\Filament\Resources\Blog\VideoReels;

use App\Filament\Resources\Blog\VideoReels\Pages\CreateVideoReel;
use App\Filament\Resources\Blog\VideoReels\Pages\EditVideoReel;
use App\Filament\Resources\Blog\VideoReels\Pages\ListVideoReels;
use App\Models\Blog\VideoReel;
use App\Models\Blog\VideoScenario;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VideoReelResource extends Resource
{
    protected static ?string $model = VideoReel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'video-reels';

    protected static ?string $modelLabel = 'ролик';

    protected static ?string $pluralModelLabel = 'Ролики';

    protected static string|UnitEnum|null $navigationGroup = 'Відео для блогу';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Результат')
                    ->schema([
                        ViewEntry::make('result')->hiddenLabel()->view('filament.blog.reel-result'),
                    ])
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Section::make('Ролик')->schema([
                    TextInput::make('title')->label('Назва')->required()->maxLength(255),
                    Repeater::make('items')->label('Кліпи по черзі')
                        ->schema([
                            Select::make('scenario_id')->hiddenLabel()->required()->searchable()
                                ->options(fn () => VideoScenario::query()->whereNotNull('video_path')->orderBy('title')->get()
                                    ->mapWithKeys(fn (VideoScenario $scenario) => [
                                        $scenario->id => $scenario->title . ($scenario->duration ? ' · ' . round($scenario->duration) . ' с' : ''),
                                    ])),
                        ])
                        ->helperText('Доступні лише сценарії з готовим відео. Перетягуйте, щоб змінити порядок; один кліп можна додати кілька разів.')
                        ->reorderable()->cloneable()
                        ->addActionLabel('Додати кліп')
                        ->defaultItems(1),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Назва')->searchable(),
                TextColumn::make('items')->label('Кліпів')
                    ->state(fn (VideoReel $record) => count($record->items ?? [])),
                TextColumn::make('duration')->label('Тривалість')->suffix(' с')->numeric(1)->placeholder('не зібрано'),
                TextColumn::make('built_at')->label('Зібрано')->dateTime('d.m.Y H:i')->sortable(),
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
            'index' => ListVideoReels::route('/'),
            'create' => CreateVideoReel::route('/create'),
            'edit' => EditVideoReel::route('/{record}/edit'),
        ];
    }
}
