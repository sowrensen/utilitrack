<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UtilityResource\Pages;
use App\Filament\Resources\UtilityResource\RelationManagers\ChildrenRelationManager;
use App\Models\Utility;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UtilityResource extends Resource
{
    protected static ?string $model = Utility::class;

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('parent_id')
                    ->label('Parent Utility')
                    ->relationship('parent', 'name')
                    ->nullable()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->default('N/A'),

                TextColumn::make('children_count')
                    ->label('Children')
                    ->counts('children')
                    ->default(0)
                    ->hiddenOn(ChildrenRelationManager::class)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUtilities::route('/'),
            'create' => Pages\CreateUtility::route('/create'),
            'edit' => Pages\EditUtility::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
