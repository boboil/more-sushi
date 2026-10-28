<?php

namespace App\Filament\Resources\Blog\VideoReels\Pages;

use App\Filament\Resources\Blog\VideoReels\VideoReelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVideoReels extends ListRecords
{
    protected static string $resource = VideoReelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
