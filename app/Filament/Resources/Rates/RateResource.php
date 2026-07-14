<?php

namespace App\Filament\Resources\Rates;

use App\Filament\Resources\Rates\Pages\CreateRate;
use App\Filament\Resources\Rates\Pages\EditRate;
use App\Filament\Resources\Rates\Pages\ListRates;
use App\Models\Rate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RateResource extends Resource
{
    protected static ?string $model = Rate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'ставка групи';

    protected static ?string $pluralModelLabel = 'Ставки груп';

    protected static string|UnitEnum|null $navigationGroup = 'Персонал';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('rate')->label('Сума для розподілу на групу')->required()->numeric()->step(0.1),
                DatePicker::make('working_date')->label('Робочий день')->required(),
                Select::make('role_id')->label('Група')->relationship('role', 'label')->required()->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('rate')->label('Сума')->numeric()->sortable(),
                TextColumn::make('working_date')->label('Робочий день')->date('d.m.Y')->sortable(),
                TextColumn::make('role.label')->label('Група'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRates::route('/'),
            'create' => CreateRate::route('/create'),
            'edit' => EditRate::route('/{record}/edit'),
        ];
    }
}
