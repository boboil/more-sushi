<?php

namespace App\Filament\Resources\Blog\VideoReels\Pages;

use App\Filament\Resources\Blog\VideoReels\VideoReelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoReel extends CreateRecord
{
    protected static string $resource = VideoReelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
