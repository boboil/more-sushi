<?php

namespace App\Filament\Resources\Shop\PromoDays\Pages;

use App\Filament\Resources\Shop\PromoDays\PromoDayResource;
use Filament\Resources\Pages\EditRecord;

class EditPromoDay extends EditRecord
{
    protected static string $resource = PromoDayResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
