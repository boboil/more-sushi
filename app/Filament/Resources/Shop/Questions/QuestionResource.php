<?php

namespace App\Filament\Resources\Shop\Questions;

use App\Filament\Resources\Shop\Questions\Pages\CreateQuestion;
use App\Filament\Resources\Shop\Questions\Pages\EditQuestion;
use App\Filament\Resources\Shop\Questions\Pages\ListQuestions;
use App\Models\Shop\Question;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'questions';

    protected static ?string $modelLabel = 'повідомлення';

    protected static ?string $pluralModelLabel = 'Зворотний звʼязок';

    protected static string|UnitEnum|null $navigationGroup = 'Магазин';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Імʼя')->required()->maxLength(255),
                TextInput::make('email')->label('E-mail')->email(),
                RichEditor::make('question')->label('Повідомлення')->columnSpanFull(),
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
                TextColumn::make('question')->label('Повідомлення')->html()->limit(80),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
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
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'edit' => EditQuestion::route('/{record}/edit'),
        ];
    }
}
