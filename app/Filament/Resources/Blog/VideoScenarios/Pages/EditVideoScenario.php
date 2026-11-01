<?php

namespace App\Filament\Resources\Blog\VideoScenarios\Pages;

use App\Filament\Resources\Blog\VideoScenarios\VideoScenarioResource;
use App\Models\Blog\VideoScenario;
use App\Services\FlowVideoExporter;
use App\Services\GeminiVideoService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Throwable;

/**
 * @property VideoScenario $record
 */
class EditVideoScenario extends EditRecord
{
    protected static string $resource = VideoScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label(fn () => $this->record->video_path ? 'Перегенерувати відео' : 'Згенерувати відео')
                ->icon('heroicon-o-sparkles')
                ->disabled(fn () => $this->record->isProcessing())
                ->requiresConfirmation()
                ->modalHeading('Згенерувати відео?')
                ->modalDescription(fn () => 'Сценарій буде збережено і відправлено в Gemini Omni Flash. Генерація платна і займає кілька хвилин — сторінку можна закрити, статус оновиться при наступному відкритті.'
                    . ($this->record->extensions ? ' Продовження поточного відео буде скинуто.' : ''))
                ->action(function (GeminiVideoService $gemini): void {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    if (empty($this->record->lines) && blank($this->record->setting)) {
                        Notification::make()->warning()->title('Додайте сцену або хоча б одну репліку')->send();

                        return;
                    }

                    try {
                        $gemini->start($this->record);
                        Notification::make()->success()->title('Генерацію запущено')->body('Відео з\'явиться тут, коли буде готове.')->send();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Gemini відхилив запит')->body($e->getMessage())->persistent()->send();
                    }
                }),
            Action::make('extend')
                ->label('Продовжити відео')
                ->icon('heroicon-o-forward')
                ->visible(fn () => $this->record->canBeExtended())
                ->modalHeading('Продовжити відео')
                ->modalDescription(fn () => 'Модель допише ~8 секунд до готового відео з тими самими персонажами і голосами. '
                    . 'Зараз ' . round($this->record->duration ?? 0) . ' с, максимум ' . VideoScenario::MAX_DURATION . ' с. Генерація платна.')
                ->modalSubmitActionLabel('Продовжити')
                ->schema([
                    VideoScenarioResource::linesRepeater('lines')->label('Нові репліки'),
                    Textarea::make('instructions')->label('Що відбувається далі')->rows(2)
                        ->placeholder('Marko picks up the knife and slices the roll into six pieces'),
                ])
                ->action(function (array $data, GeminiVideoService $gemini): void {
                    try {
                        $gemini->extend($this->record, $data['lines'] ?? [], $data['instructions'] ?? null);
                        Notification::make()->success()->title('Продовження запущено')->send();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Gemini відхилив запит')->body($e->getMessage())->persistent()->send();
                    }
                }),
            ActionGroup::make([
                Action::make('exportFlow')
                    ->label('Експорт для Flow')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->action(function (FlowVideoExporter $flow) {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                        try {
                            $zip = $flow->export($this->record);
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title('Не вдалося зібрати архів')->body($e->getMessage())->send();

                            return null;
                        }

                        return response()->download($zip, 'flow-' . $this->record->id . '-' . Str::slug($this->record->title) . '.zip')->deleteFileAfterSend();
                    }),
                Action::make('uploadFlowVideo')
                    ->label('Завантажити відео з Flow')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->disabled(fn () => $this->record->isProcessing())
                    ->modalHeading('Завантажити готове відео')
                    ->modalDescription(fn () => 'mp4, скачаний з Google Flow, стане відео цього сценарію: його можна дивитись тут і додавати в ролики.'
                        . ($this->record->video_path ? ' Поточне відео буде замінено.' : ''))
                    ->modalSubmitActionLabel('Завантажити')
                    ->schema([
                        FileUpload::make('video')->label('Відео (mp4)')->required()
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime'])->maxSize(100 * 1024)
                            ->disk('public_root')->directory(GeminiVideoService::VIDEO_DIRECTORY)->visibility('public')
                            ->getUploadedFileNameForStorageUsing(fn ($file) => $this->record->id . '-' . now()->format('Ymd-His') . '-flow.' . ($file->guessExtension() ?: 'mp4')),
                    ])
                    ->action(function (array $data, FlowVideoExporter $flow): void {
                        try {
                            $flow->attach($this->record, $data['video']);
                            Notification::make()->success()->title('Відео завантажено')->send();
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title('Не вдалося зберегти відео')->body($e->getMessage())->send();
                        }
                    }),
            ])->label('Google Flow')->icon('heroicon-o-film')->color('gray')->button(),
            Action::make('checkStatus')
                ->label('Перевірити статус')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => $this->record->isProcessing())
                ->action(fn () => $this->pollVideo(notifyIfPending: true)),
            Action::make('download')
                ->label('Завантажити mp4')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->video_path)
                ->url(fn () => asset($this->record->video_path), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }

    public function pollVideo(bool $notifyIfPending = false): void
    {
        $this->record->refresh();

        try {
            $changed = app(GeminiVideoService::class)->refresh($this->record);
        } catch (Throwable $e) {
            report($e);
            Notification::make()->danger()->title('Не вдалося перевірити статус')->body($e->getMessage())->send();

            return;
        }

        if ($changed) {
            $this->record->error
                ? Notification::make()->danger()->title('Генерація не вдалась')->body($this->record->error)->persistent()->send()
                : Notification::make()->success()->title('Відео готове')->send();
        } elseif ($notifyIfPending) {
            Notification::make()->info()->title('Ще генерується')->send();
        }
    }

    public function getFooter(): ?View
    {
        return view('filament.blog.video-poll', ['processing' => $this->record->isProcessing()]);
    }
}
