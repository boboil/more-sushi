<?php

namespace App\Filament\Resources\Shop\Products\Pages;

use App\Filament\Resources\Shop\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Http\Controllers\Shop\ProductController;
use App\Services\PosterProductSync;
use Throwable;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncPoster')
                ->label('Синхронізувати з Poster')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalDescription('Товари, яких немає в Poster або які там приховані, буде вимкнено на сайті. Решту — увімкнено, ціни й фото оновлено.')
                ->action(function (): void {
                    try {
                        $plan = app(PosterProductSync::class)->run();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Синхронізацію не виконано')->body($e->getMessage())->persistent()->send();

                        return;
                    }
                    $disabled = $plan['disable']->pluck('title')->map(fn ($t) => trim($t))->implode(', ');
                    Notification::make()->success()->title('Синхронізацію завершено')
                        ->body("Увімкнено: {$plan['enable']->count()}, вимкнено: {$plan['disable']->count()}, нових: {$plan['create']->count()}." . ($disabled ? " Вимкнено: {$disabled}." : ''))
                        ->persistent()
                        ->send();
                }),
            Action::make('fixImages')
                ->label('Оптимізувати зображення')
                ->icon('heroicon-o-photo')
                ->requiresConfirmation()
                ->action(function (): void {
                    app(ProductController::class)->fixImagesInProduct();
                    Notification::make()->success()->title('Зображення оптимізовано')->send();
                }),
            CreateAction::make(),
        ];
    }
}
