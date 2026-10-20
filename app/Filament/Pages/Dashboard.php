<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Analytics\OrderPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Аналітика';

    public function filtersForm(Schema $schema): Schema
    {
        $custom = fn (Get $get) => $get('period') === 'custom';

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('period')
                            ->label('Період')
                            ->options(OrderPeriod::PRESETS)
                            ->default(OrderPeriod::DEFAULT_PRESET)
                            ->selectablePlaceholder(false)
                            ->native(false),
                        DatePicker::make('from')
                            ->label('З')
                            ->displayFormat('d.m.Y')
                            ->maxDate(now())
                            ->visible($custom)
                            ->required($custom),
                        DatePicker::make('to')
                            ->label('По')
                            ->displayFormat('d.m.Y')
                            ->maxDate(now())
                            ->visible($custom),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public function getColumns(): int|array
    {
        return ['md' => 2];
    }
}
