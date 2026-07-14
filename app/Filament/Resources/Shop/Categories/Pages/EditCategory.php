<?php

namespace App\Filament\Resources\Shop\Categories\Pages;

use App\Filament\Resources\Shop\Categories\CategoryResource;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
