<?php

namespace App\Filament\Resources\Blog\VideoCharacters\Pages;

use App\Filament\Resources\Blog\VideoCharacters\VideoCharacterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVideoCharacter extends EditRecord
{
    protected static string $resource = VideoCharacterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
