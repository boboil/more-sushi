<?php

namespace App\Filament\Resources\Blog\VideoReels\Pages;

use App\Filament\Resources\Blog\VideoReels\VideoReelResource;
use App\Models\Blog\VideoReel;
use App\Services\VideoReelBuilder;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

/**
 * @property VideoReel $record
 */
class EditVideoReel extends EditRecord
{
    protected static string $resource = VideoReelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('build')
                ->label(fn () => $this->record->video_path ? 'Перезібрати ролик' : 'Зібрати ролик')
                ->icon('heroicon-o-film')
                ->action(function (VideoReelBuilder $builder): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    try {
                        $builder->build($this->record);
                        Notification::make()->success()->title('Ролик зібрано')
                            ->body('Тривалість: ' . round($this->record->duration ?? 0) . ' с')->send();
                    } catch (Throwable $e) {
                        report($e);
                        $this->record->forceFill(['error' => $e->getMessage()])->save();
                        Notification::make()->danger()->title('Не вдалося зібрати ролик')->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('download')
                ->label('Завантажити mp4')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->video_path)
                ->url(fn () => asset($this->record->video_path), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
