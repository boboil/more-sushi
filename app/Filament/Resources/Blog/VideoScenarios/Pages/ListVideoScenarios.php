<?php

namespace App\Filament\Resources\Blog\VideoScenarios\Pages;

use App\Filament\Resources\Blog\VideoScenarios\VideoScenarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVideoScenarios extends ListRecords
{
    protected static string $resource = VideoScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
