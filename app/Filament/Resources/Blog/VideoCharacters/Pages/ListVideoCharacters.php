<?php

namespace App\Filament\Resources\Blog\VideoCharacters\Pages;

use App\Filament\Resources\Blog\VideoCharacters\VideoCharacterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVideoCharacters extends ListRecords
{
    protected static string $resource = VideoCharacterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
