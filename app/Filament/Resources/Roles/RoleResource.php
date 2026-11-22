<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Посади і ставки: коефіцієнт посади для розподілу фонду зарплати + хто на цій посаді.
 */
class RoleResource extends Resource
{
    /** Ролі доступу, які використовуються в коді: їх не можна видалити. */
    public const SYSTEM_ROLES = ['admin', 'manager'];

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $modelLabel = 'посада';

    protected static ?string $pluralModelLabel = 'Посади і ставки';

    protected static string|UnitEnum|null $navigationGroup = 'Персонал';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('label')->label('Назва посади')->required()->maxLength(255),
                    TextInput::make('salary_coefficient')
                        ->label('Ставка (коефіцієнт)')
                        ->helperText('Частка фонду дня = години × коефіцієнт. Порожньо — посада не бере участі в розподілі.')
                        ->numeric()->minValue(0)->maxValue(5)->step(0.05),
                    Select::make('users')
                        ->label('Працівники на посаді')
                        ->relationship('users', 'name')
                        ->multiple()->preload()->searchable()
                        ->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Посада')->searchable()->sortable(),
                TextColumn::make('salary_coefficient')->label('Ставка')->numeric(2)->placeholder('—')->sortable(),
                TextColumn::make('users.name')->label('Працівники')->badge()->wrap(),
            ])
            ->defaultSort('id')
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

    public static function canDelete(Model $record): bool
    {
        return static::canAccess() && ! in_array($record->name, self::SYSTEM_ROLES, true);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
