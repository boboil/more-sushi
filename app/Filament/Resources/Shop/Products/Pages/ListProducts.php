<?php

namespace App\Filament\Resources\Shop\Products\Pages;

use App\Filament\Resources\Shop\Products\ProductResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Utils\PosterAuthController;
use Illuminate\Http\Request;

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
                ->action(function (): void {
                    app(PosterAuthController::class)->getProducts(Request::create('/admin/products/sync', 'POST'));
                    Notification::make()->success()->title('Синхронізацію завершено')->send();
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
