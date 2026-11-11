<?php

namespace App\Filament\Widgets\Analytics;

use Filament\Tables\Columns\TextColumn;

/**
 * Однакові стовпці для таблиць-розбивок: замовлення, виручка, середній чек, частка.
 */
final class BreakdownTable
{
    /**
     * @return list<TextColumn>
     */
    public static function columns(string $label): array
    {
        return [
            TextColumn::make('label')->label($label)->wrap(),
            TextColumn::make('orders')->label('Замовлень')->numeric(),
            TextColumn::make('revenue')->label('Виручка')->formatStateUsing(fn ($state) => OrderAnalytics::money((float) $state)),
            TextColumn::make('average')->label('Середній чек')->formatStateUsing(fn ($state) => OrderAnalytics::money((float) $state)),
            TextColumn::make('share')->label('Частка')->formatStateUsing(fn ($state) => number_format((float) $state, 1, ',', ' ') . '%'),
        ];
    }
}
