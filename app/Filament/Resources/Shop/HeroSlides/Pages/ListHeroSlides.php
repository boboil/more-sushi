<?php

namespace App\Filament\Resources\Shop\HeroSlides\Pages;

use App\Filament\Resources\Shop\HeroSlides\HeroSlideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHeroSlides extends ListRecords
{
    protected static string $resource = HeroSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
