<?php

namespace App\Filament\Resources\Rates\Pages;

use App\Filament\Resources\Rates\RateResource;
use Filament\Resources\Pages\EditRecord;

class EditRate extends EditRecord
{
    protected static string $resource = RateResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
