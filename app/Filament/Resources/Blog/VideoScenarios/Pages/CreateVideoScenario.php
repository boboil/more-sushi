<?php

namespace App\Filament\Resources\Blog\VideoScenarios\Pages;

use App\Filament\Resources\Blog\VideoScenarios\VideoScenarioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoScenario extends CreateRecord
{
    protected static string $resource = VideoScenarioResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
