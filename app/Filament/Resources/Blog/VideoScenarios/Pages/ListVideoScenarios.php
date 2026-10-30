<?php

namespace App\Filament\Resources\Blog\VideoScenarios\Pages;

use App\Filament\Resources\Blog\VideoScenarios\VideoScenarioResource;
use App\Services\VideoScenarioImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

class ListVideoScenarios extends ListRecords
{
    protected static string $resource = VideoScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('chatGptPrompt')
                ->label('Промпт для ChatGPT')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('gray')
                ->action(fn (VideoScenarioImporter $importer) => response()->streamDownload(
                    fn () => print($importer->chatGptPrompt()),
                    'chatgpt-video-prompt.md',
                    ['Content-Type' => 'text/markdown; charset=UTF-8'],
                )),
            Action::make('import')
                ->label('Імпорт з файлу')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->modalHeading('Імпорт сценаріїв')
                ->modalDescription('JSON-відповідь ChatGPT: завантажте файлом або вставте текстом. Персонажі шукаються за ім\'ям, нові створюються.')
                ->modalSubmitActionLabel('Імпортувати')
                ->schema([
                    FileUpload::make('file')->label('Файл (.json або .txt)')
                        ->acceptedFileTypes(['application/json', 'text/plain', 'text/markdown'])
                        ->maxSize(1024)->storeFiles(false),
                    Textarea::make('json')->label('Або вставте JSON')->rows(8),
                    Toggle::make('update_characters')->label('Оновити опис і голос наявних персонажів з файлу'),
                ])
                ->action(function (array $data, VideoScenarioImporter $importer, Action $action) {
                    $file = $data['file'] ?? null;
                    $content = $file instanceof TemporaryUploadedFile ? $file->get() : ($data['json'] ?? '');

                    try {
                        if (blank($content)) {
                            throw new RuntimeException('Завантажте файл або вставте JSON');
                        }
                        $result = $importer->import($importer->parse($content), (bool) ($data['update_characters'] ?? false));
                    } catch (RuntimeException $e) {
                        Notification::make()->title('Не вдалося імпортувати')->body(new HtmlString(nl2br(e($e->getMessage()))))->danger()->persistent()->send();
                        $action->halt();

                        return;
                    }

                    $created = $result['created_characters'];
                    Notification::make()
                        ->title('Імпортовано сценаріїв: ' . $result['scenarios']->count())
                        ->body(collect([
                            $created->isNotEmpty() ? 'Нові персонажі: ' . $created->pluck('name')->implode(', ') . ' — додайте їм фото.' : null,
                            $result['reel'] ? "Створено ролик «{$result['reel']->title}»." : null,
                        ])->filter()->implode(' '))
                        ->success()->send();

                    if ($result['scenarios']->count() === 1) {
                        $this->redirect(VideoScenarioResource::getUrl('edit', ['record' => $result['scenarios']->first()]));
                    }
                }),
            CreateAction::make(),
        ];
    }
}
