<?php

namespace App\Filament\Resources\Blog\VideoScenarios;

use App\Filament\Resources\Blog\VideoScenarios\Pages\CreateVideoScenario;
use App\Filament\Resources\Blog\VideoScenarios\Pages\EditVideoScenario;
use App\Filament\Resources\Blog\VideoScenarios\Pages\ListVideoScenarios;
use App\Models\Blog\VideoCharacter;
use App\Models\Blog\VideoScenario;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VideoScenarioResource extends Resource
{
    protected static ?string $model = VideoScenario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFilm;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'video-scenarios';

    protected static ?string $modelLabel = 'сценарій';

    protected static ?string $pluralModelLabel = 'Сценарії відео';

    protected static string|UnitEnum|null $navigationGroup = 'Відео для блогу';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Результат')
                    ->schema([
                        ViewEntry::make('result')->hiddenLabel()->view('filament.blog.video-result'),
                    ])
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Section::make('Сценарій')->schema([
                    TextInput::make('title')->label('Назва')->required()->maxLength(255)->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('aspect_ratio')->label('Формат')->required()->default('9:16')->selectablePlaceholder(false)
                            ->options(['9:16' => '9:16 — вертикальне (Reels, Shorts, TikTok)', '16:9' => '16:9 — горизонтальне']),
                        Select::make('resolution')->label('Якість')->required()->default('720p')->selectablePlaceholder(false)
                            ->options(['360p' => '360p (чернетка)', '720p' => '720p', '1080p' => '1080p', '4k' => '4K']),
                        TextInput::make('language')->label('Мова діалогів')->required()->default('українська')->maxLength(50),
                    ]),
                ])->columnSpanFull(),
                Section::make('Сцена')->schema([
                    Textarea::make('setting')->label('Де і що відбувається')->rows(3)
                        ->placeholder('Кухня ресторану ввечері, на столі сет «Філадельфія», шеф викладає рол на дошку'),
                    Textarea::make('style')->label('Візуальний стиль, камера, музика')->rows(2)
                        ->placeholder('Теплий світ, кінематографічно, повільний наїзд камери, легка фонова музика'),
                    FileUpload::make('start_frame')->label('Стартовий кадр')->image()->maxSize(10 * 1024)
                        ->disk('public_root')->directory('blog-video/references')->visibility('public')
                        ->helperText('Готова композиція, з якої почнеться відео. Якщо задано, фото персонажів у модель не відправляються — тільки цей кадр і описи.')
                        ->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
                Section::make('Діалог')
                    ->description('Репліки йдуть у відео в цьому порядку. Перетягуйте, щоб змінити порядок. Ремарка без тексту — це дія без слів.')
                    ->schema([
                        self::linesRepeater('lines'),
                    ])->columnSpanFull(),
                Section::make('Додатково')->schema([
                    FileUpload::make('extra_references')->label('Референси сцени (локація, страви, реквізит)')
                        ->image()->multiple()->reorderable()->maxFiles(5)->maxSize(10 * 1024)->panelLayout('grid')
                        ->disk('public_root')->directory('blog-video/references')->visibility('public'),
                    Textarea::make('extra_instructions')->label('Додаткові вказівки для моделі')->rows(3)
                        ->helperText('Додаються в кінець промпта як є. Краще англійською.'),
                ])->collapsible()->columnSpanFull(),
            ]);
    }

    /**
     * Репліки сценарію; та сама форма використовується для продовження відео.
     */
    public static function linesRepeater(string $name): Repeater
    {
        return Repeater::make($name)->hiddenLabel()
            ->schema([
                Select::make('character_id')->label('Персонаж')
                    ->options(fn () => VideoCharacter::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()->required()
                    ->createOptionForm([
                        TextInput::make('name')->label("Ім'я")->required()->maxLength(255),
                        Textarea::make('description')->label('Зовнішність')->rows(2),
                    ])
                    ->createOptionUsing(fn (array $data) => VideoCharacter::query()->create($data)->getKey())
                    ->columnSpan(1),
                TextInput::make('direction')->label('Ремарка')->maxLength(255)
                    ->placeholder('усміхається, бере паличками рол')
                    ->columnSpan(2),
                Textarea::make('text')->label('Репліка')->rows(2)->columnSpanFull(),
            ])
            ->columns(3)
            ->itemLabel(fn (array $state) => VideoCharacter::query()->find($state['character_id'] ?? null)?->name)
            ->reorderable()->collapsible()->cloneable()
            ->addActionLabel('Додати репліку')
            ->defaultItems(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Назва')->searchable(),
                TextColumn::make('status')->label('Статус')->badge()
                    ->formatStateUsing(fn (string $state) => VideoScenario::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        VideoScenario::STATUS_DONE => 'success',
                        VideoScenario::STATUS_PROCESSING => 'warning',
                        VideoScenario::STATUS_FAILED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('lines')->label('Реплік')
                    ->state(fn (VideoScenario $record) => count($record->lines ?? [])),
                TextColumn::make('aspect_ratio')->label('Формат'),
                TextColumn::make('duration')->label('Тривалість')->suffix(' с')->numeric(1)->placeholder('—'),
                TextColumn::make('cost')->label('Витрачено')->money('USD')->sortable()
                    ->summarize(Sum::make()->label('Разом')->money('USD')),
                TextColumn::make('generated_at')->label('Згенеровано')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('updated_at')->label('Змінено')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(VideoScenario::STATUSES),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Копія')
                    ->excludeAttributes(['status', 'interaction_id', 'previous_interaction_id', 'extending', 'extensions', 'file_id', 'video_path', 'duration', 'cost', 'last_cost', 'last_usage', 'last_prompt', 'error', 'generated_at'])
                    ->beforeReplicaSaved(fn (VideoScenario $replica) => $replica->title .= ' (копія)'),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVideoScenarios::route('/'),
            'create' => CreateVideoScenario::route('/create'),
            'edit' => EditVideoScenario::route('/{record}/edit'),
        ];
    }
}
