<?php

namespace App\Filament\Resources\WorkingHours;

use App\Filament\Resources\WorkingHours\Pages\CreateWorkingHours;
use App\Filament\Resources\WorkingHours\Pages\EditWorkingHours;
use App\Filament\Resources\WorkingHours\Pages\ListWorkingHours;
use App\Models\WorkingHours;
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

class WorkingHoursResource extends Resource
{
    protected static ?string $model = WorkingHours::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'відпрацьовані години';

    protected static ?string $pluralModelLabel = 'Відпрацьовані години';

    protected static string|UnitEnum|null $navigationGroup = 'Персонал';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('hours')->label('Відпрацьовані години')->numeric()->step(0.1)->required(),
                DatePicker::make('working_day')->label('Робочий день')->required(),
                Select::make('user_id')->label('Працівник')->relationship('user', 'name')->searchable()->preload()->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hours')->label('Години')->numeric()->sortable(),
                TextColumn::make('working_day')->label('Робочий день')->date('d.m.Y')->sortable(),
                TextColumn::make('user.name')->label('Працівник')->searchable(),
                TextColumn::make('user.email')->label('Логін працівника')->searchable(),
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
            'index' => ListWorkingHours::route('/'),
            'create' => CreateWorkingHours::route('/create'),
            'edit' => EditWorkingHours::route('/{record}/edit'),
        ];
    }
}
