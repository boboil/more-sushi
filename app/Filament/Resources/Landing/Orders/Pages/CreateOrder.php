<?php

namespace App\Filament\Resources\Landing\Orders\Pages;

use App\Filament\Resources\Concerns\InteractsWithOrderProducts;
use App\Filament\Resources\Landing\Orders\OrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    use InteractsWithOrderProducts;

    protected static string $resource = OrderResource::class;
}
