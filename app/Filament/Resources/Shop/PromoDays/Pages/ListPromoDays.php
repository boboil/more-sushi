<?php

namespace App\Filament\Resources\Shop\PromoDays\Pages;

use App\Filament\Resources\Shop\PromoDays\PromoDayResource;
use App\Services\Promo\PromoDays;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPromoDays extends ListRecords
{
    protected static string $resource = PromoDayResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return app(PromoDays::class)->enabled()
            ? 'Лендинги /promo/… УВІМКНЕНІ: сторінки відкриваються, знижки дня діють на сайті.'
            : 'Лендинги /promo/… вимкнені: сторінки віддають 404, знижки дня не застосовуються.';
    }

    protected function getHeaderActions(): array
    {
        $enabled = app(PromoDays::class)->enabled();

        return [
            Action::make('togglePromoLanding')
                ->label($enabled ? 'Вимкнути лендинги' : 'Увімкнути лендинги')
                ->icon($enabled ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color($enabled ? 'danger' : 'success')
                ->requiresConfirmation()
                ->modalDescription($enabled
                    ? 'Сторінки /promo/… перестануть відкриватися, знижки дня перестануть діяти.'
                    : 'Сторінки /promo/… стануть доступні, у години акції знижка діятиме на товари дня по всьому сайту.')
                ->action(function () use ($enabled): void {
                    app(PromoDays::class)->setEnabled(! $enabled);
                    Notification::make()->success()->title($enabled ? 'Лендинги вимкнено' : 'Лендинги увімкнено')
                        ->body('На сайті зміни з\'являться протягом хвилини.')->send();
                }),
        ];
    }
}
