<?php

namespace App\Filament\Resources\Blog\Concerns;

use App\Models\Blog\VideoScenario;
use App\Services\YouTubeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Кнопки YouTube на сторінках сценарію і ролика.
 */
trait HasYouTubeActions
{
    /**
     * @return array<Action>
     */
    protected function youTubeActions(): array
    {
        return [
            Action::make('youtubeConnect')
                ->label('Підключити YouTube')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->visible(fn (YouTubeService $youtube) => $this->record->video_path && $youtube->configured() && ! $youtube->connection())
                ->url(fn () => route('filament.admin.youtube.connect')),
            Action::make('youtubeUpload')
                ->label(fn () => $this->record->youtube_video_id ? 'На YouTube ще раз' : 'На YouTube')
                ->icon('heroicon-o-arrow-up-on-square')
                ->color('gray')
                ->visible(fn (YouTubeService $youtube) => $this->record->video_path && $youtube->connection())
                ->disabled(fn () => $this->record instanceof VideoScenario && $this->record->isProcessing())
                ->modalHeading('Завантажити на YouTube')
                ->modalDescription(fn (YouTubeService $youtube) => 'Канал: ' . $youtube->connection()?->channel_title . '.'
                    . ($this->record->youtube_video_id ? ' Це відео вже завантажували: на каналі з\'явиться ще одна копія.' : ''))
                ->modalSubmitActionLabel('Завантажити')
                ->schema([
                    TextInput::make('title')->label('Заголовок')->required()->maxLength(100)
                        ->notRegex('/[<>]/')
                        ->default(fn () => $this->record->title),
                    Textarea::make('description')->label('Опис')->rows(6)->maxLength(5000)
                        ->notRegex('/[<>]/')
                        ->default(YouTubeService::DEFAULT_DESCRIPTION),
                    Select::make('privacy')->label('Видимість')->required()->selectablePlaceholder(false)
                        ->options(YouTubeService::PRIVACY)->default('unlisted'),
                    Toggle::make('synthetic')->label('Реалістичний контент, створений ШІ')->default(true)
                        ->helperText('YouTube вимагає позначати реалістичні згенеровані сцени і людей. Вимикайте лише для явно мультяшного стилю.'),
                ])
                ->action(function (array $data, YouTubeService $youtube): void {
                    @set_time_limit(600);

                    try {
                        $id = $youtube->upload($this->record, $data);
                        Notification::make()->success()->title('Відео завантажено на YouTube')->body("https://youtu.be/{$id}")->persistent()->send();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('YouTube не прийняв відео')->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('youtubeOpen')
                ->label('Відкрити на YouTube')
                ->icon('heroicon-o-play')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->youtube_video_id)
                ->url(fn () => "https://youtu.be/{$this->record->youtube_video_id}", shouldOpenInNewTab: true),
        ];
    }
}
