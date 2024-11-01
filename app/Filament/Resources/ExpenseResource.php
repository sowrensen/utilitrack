<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Utility')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('৳')
                    ->required(),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\TextInput::make('usable')
                            ->required()
                            ->numeric()
                            ->default(null)
                            ->minValue(0),
                        Forms\Components\TextInput::make('leftover')
                            ->numeric()
                            ->default(null)
                            ->minValue(0),
                        Forms\Components\Select::make('unit')
                            ->options(['BDT' => 'BDT', 'LITER' => 'LITER'])
                            ->requiredWith(['usable', 'leftover']),
                    ])->columns(3)->columnSpanFull(),
                Forms\Components\DatePicker::make('purchase_date')
                    ->suffixIcon('heroicon-o-calendar-date-range')
                    ->nullable()
                    ->native(false)
                    ->maxDate(today()),
                Forms\Components\DatePicker::make('usage_date')
                    ->suffixIcon('heroicon-o-calendar-date-range')
                    ->nullable()
                    ->native(false)
                    ->maxDate(today())
                    ->afterOrEqual('purchase_date'),
                Forms\Components\Textarea::make('note')
                    ->columnSpanFull()->rows(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('purchase_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usable')
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$expense->usable} {$expense->unit}"),
                Tables\Columns\TextColumn::make('leftover')
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$expense->leftover} {$expense->unit}"),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_date')
                    ->date()
                    ->sortable(),
                // Tables\Columns\TextColumn::make('interval')->default(0),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('category', titleAttribute: 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
