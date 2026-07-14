<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'користувач';

    protected static ?string $pluralModelLabel = 'Користувачі';

    protected static string|UnitEnum|null $navigationGroup = 'Персонал';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Обліковий запис')->schema([
                    TextInput::make('name')->label('Імʼя')->required()->maxLength(255),
                    TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
                    TextInput::make('password')
                        ->label('Пароль')
                        ->password()
                        ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->minLength(6),
                    TextInput::make('rate')->label('Ставка за годину, UAH')->numeric()->step(0.1)->required(),
                    TextInput::make('poster_user_id')->label('ID користувача в Poster')->numeric()->required(),
                    Select::make('roles')->label('Ролі')->relationship('roles', 'label')->multiple()->preload()->searchable(),
                ])->columns(2)->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Імʼя')
                    ->searchable(),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('rate')->label('Ставка за годину')->numeric(),
                TextColumn::make('roles.label')->label('Ролі')->badge(),
            ])
            ->filters([
                SelectFilter::make('roles')->label('Роль')->relationship('roles', 'label'),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
