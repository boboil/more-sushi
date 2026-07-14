<?php

namespace App\Filament\Resources\Landing\Orders\Pages;

use App\Filament\Resources\Concerns\InteractsWithOrderProducts;
use App\Filament\Resources\Landing\Orders\OrderResource;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    use InteractsWithOrderProducts;

    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
