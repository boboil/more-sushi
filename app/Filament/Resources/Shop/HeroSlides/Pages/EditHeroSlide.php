<?php

namespace App\Filament\Resources\Shop\HeroSlides\Pages;

use App\Filament\Resources\Shop\HeroSlides\HeroSlideResource;
use Filament\Resources\Pages\EditRecord;

class EditHeroSlide extends EditRecord
{
    protected static string $resource = HeroSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
