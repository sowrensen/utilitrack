<?php

namespace App\Filament\Resources\Categories;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\Categories\RelationManagers\ChildrenRelationManager;
use App\Models\Category;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Configuration';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-fire';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Group::make([
                            Select::make('parent_id')
                                ->label('Parent category')
                                ->relationship('parent', 'name')
                                ->preload()
                                ->nullable()
                                ->searchable(),

                            TextInput::make('unit')
                                ->placeholder('BDT, USD, LITER, etc.')
                                ->nullable(),
                        ])->columns(2),

                        Checkbox::make('has_usage_per_day')
                            ->helperText('Usage per day will not be calculated if disabled'),
                    ])->columnSpan(1),
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
                    ->placeholder('N/A'),

                TextColumn::make('unit')
                    ->placeholder('N/A'),

                TextColumn::make('children_count')
                    ->label('Subcategories')
                    ->counts('children')
                    ->placeholder(0)
                    ->hiddenOn(ChildrenRelationManager::class)
                    ->sortable(),

                TextColumn::make('expenses_count')
                    ->label('Entries')
                    ->counts('expenses')
                    ->placeholder(0)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
