<?php

namespace App\Filament\Resources\Shop\Questions\Pages;

use App\Filament\Resources\Shop\Questions\QuestionResource;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
