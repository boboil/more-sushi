<?php

namespace App\Filament\Resources\WorkingHours\Pages;

use App\Filament\Resources\WorkingHours\WorkingHoursResource;
use Filament\Resources\Pages\EditRecord;

class EditWorkingHours extends EditRecord
{
    protected static string $resource = WorkingHoursResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
