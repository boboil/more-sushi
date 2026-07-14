<?php

namespace App\Filament\Pages;

use App\Models\Shop\Delivery;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class DeliverySettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Доставка';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    protected static ?string $title = 'Вартість доставки';

    protected static ?string $slug = 'delivery';

    protected string $view = 'filament.pages.delivery-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'cost' => Delivery::query()->value('cost') ?? 0,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('cost')->label('Вартість доставки, грн')->numeric()->minValue(0)->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $delivery = Delivery::query()->first() ?? new Delivery();
        $delivery->fill($data)->save();

        Notification::make()->success()->title('Вартість доставки збережено')->send();
    }
}
