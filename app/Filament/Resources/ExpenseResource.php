<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ExpenseExporter;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-bangladeshi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Utility')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->columnSpanFull()
                            ->rows(4),
                    ]),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefixIcon('heroicon-o-currency-bangladeshi')
                            ->required(),
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('usable')
                                ->required()
                                ->numeric()
                                ->default(null)
                                ->minValue(0),
                            Forms\Components\TextInput::make('leftover')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ])->columns(2),
                        Forms\Components\Select::make('unit')
                            ->options(['BDT' => 'BDT', 'LITER' => 'LITER'])
                            ->requiredWith(['usable', 'leftover']),
                    ])->columnSpan(1),
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\DatePicker::make('purchase_date')
                            ->live()
                            ->suffixIcon('heroicon-o-calendar-date-range')
                            ->required()
                            ->maxDate(today())
                            ->native(false),
                        Forms\Components\Toggle::make('use_same_date')
                            ->live()
                            ->disabled(fn (Get $get) => empty($get('purchase_date')))
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if ($state) {
                                    $set('usage_date', $get('purchase_date'));

                                    return;
                                }
                                $set('usage_date', null);
                            }),
                        Forms\Components\DatePicker::make('usage_date')
                            ->suffixIcon('heroicon-o-calendar-date-range')
                            ->required()
                            ->disabled(fn (Get $get) => $get('use_same_date'))
                            ->dehydrated()
                            ->maxDate(today())
                            ->native(false)
                            ->afterOrEqual('purchase_date'),
                    ])->columnSpan(1),

                // Control logging in google sheet
                Forms\Components\Checkbox::make('append_to_google_sheets')
                    ->label('Append to Google Sheets')
                    ->visible(config('services.google.sheet_id') && config('services.google.cloud_config_path'))
                    ->hiddenOn(Pages\EditExpense::class)
                    ->default(false),

                // Hidden attributes which will be calculated automatically, we need them
                // here to trigger the calculation located in Expense model.
                Forms\Components\Hidden::make('interval')->default(0),
                Forms\Components\Hidden::make('usage_per_day')->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('purchase_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->weight(FontWeight::Bold)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->color(Color::Amber)
                    ->fontFamily(FontFamily::Mono)
                    ->alignRight()
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->money(divideBy: 100)
                    ),
                Tables\Columns\TextColumn::make('usable')
                    ->alignRight()
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$state} {$expense->unit}")
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->visible(fn (Component $livewire) => ! empty($livewire->tableFilters['categories']['value']))
                            ->formatStateUsing(fn ($state) => round($state / 100, 2))
                    ),
                Tables\Columns\TextColumn::make('leftover')
                    ->alignRight()
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$state} {$expense->unit}")
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->visible(fn (Component $livewire) => ! empty($livewire->tableFilters['categories']['value']))
                            ->formatStateUsing(fn ($state) => round($state / 100, 2))
                    ),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->wrapHeader()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_date')
                    ->date()
                    ->wrapHeader(),
                Tables\Columns\TextColumn::make('interval')
                    ->label('Interval (days/months)')
                    ->wrapHeader()
                    ->alignCenter()
                    ->formatStateUsing(function (Expense $expense) {
                        return "{$expense->interval} / ~{$expense->interval_months}";
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_per_day')
                    ->label('Usage/day')
                    ->alignRight()
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$state} {$expense->unit}")
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Average::make()
                            ->visible(fn (Component $livewire) => ! empty($livewire->tableFilters['categories']['value']))
                            ->formatStateUsing(fn ($state) => round($state / 100, 2))
                    ),
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

                Filter::make('usage_date')
                    ->form([
                        DatePicker::make('usage_from')
                            ->native(false)
                            ->maxDate(today()),
                        DatePicker::make('usage_until')
                            ->native(false)
                            ->maxDate(today())
                            ->default(today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['usage_from'] && $data['usage_until'],
                                fn (Builder $query): Builder => $query->whereBetween('usage_date', [$data['usage_from'], $data['usage_until']]),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['usage_from'] || ! $data['usage_until']) {
                            return null;
                        }

                        return 'Usage: '
                            .Carbon::parse($data['usage_from'])->format('M d, Y')
                            .' – '
                            .Carbon::parse($data['usage_until'])->format('M d, Y');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_note')
                    ->label(false)
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->slideOver()
                    ->modalIcon('heroicon-o-document-text')
                    ->modalIconColor(Color::Emerald)
                    ->modalHeading('Viewing Note')
                    ->modalContent(fn (Expense $expense) => view('filament.pages.actions.note', ['expense' => $expense]))
                    ->modalAlignment(Alignment::Left)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalWidth(MaxWidth::Medium)
                    ->color(Color::Zinc)->visible(fn (Expense $expense) => ! empty($expense->note)),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export to file')
                        ->exporter(ExpenseExporter::class),
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
