<?php

namespace App\Filament\Resources\Expenses;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Checkbox;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Exports\ExpenseExporter;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-currency-bangladeshi';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('category_id')
                            ->label('Utility')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Textarea::make('note')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
                Section::make()
                    ->schema([
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefixIcon('heroicon-o-currency-bangladeshi')
                            ->required(),
                        Group::make([
                            TextInput::make('usable')
                                ->label('Usable quantity')
                                ->required()
                                ->numeric()
                                ->default(null)
                                ->minValue(0),
                            TextInput::make('leftover')
                                ->label('Leftover quantity')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ])->columns(2),
                    ])->columnSpan(1),
                Section::make()
                    ->schema([
                        DatePicker::make('purchase_date')
                            ->live()
                            ->suffixIcon('heroicon-o-calendar-date-range')
                            ->required()
                            ->maxDate(today())
                            ->native(false),
                        Toggle::make('use_same_date')
                            ->live()
                            ->disabled(fn (Get $get) => empty($get('purchase_date')))
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if ($state) {
                                    $set('usage_date', $get('purchase_date'));

                                    return;
                                }
                                $set('usage_date', null);
                            }),
                        DatePicker::make('usage_date')
                            ->suffixIcon('heroicon-o-calendar-date-range')
                            ->disabled(fn (Get $get) => $get('use_same_date'))
                            ->dehydrated()
                            ->maxDate(today())
                            ->native(false)
                            ->afterOrEqual('purchase_date'),
                    ])->columnSpan(1),

                // Control logging in google sheet
                Checkbox::make('append_to_google_sheets')
                    ->label('Append to GSheets')
                    ->visible(config('services.google.sheet_id') && config('services.google.cloud_config_path'))
                    ->hiddenOn(EditExpense::class)
                    ->default(false),

                // Hidden attributes which will be calculated automatically, we need them
                // here to trigger the calculation located in Expense model.
                Hidden::make('interval')->default(0),
                Hidden::make('usage_per_day')->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('purchase_date', 'desc')
            ->columns([
                TextColumn::make('category.name')
                    ->numeric()
                    ->weight(FontWeight::Bold)
                    ->sortable(),
                TextColumn::make('price')
                    ->money()
                    ->color(Color::Amber)
                    ->fontFamily(FontFamily::Mono)
                    ->alignRight()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->money(divideBy: 100)
                    ),
                TextColumn::make('usable')
                    ->alignRight()
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$state} {$expense->category?->unit}")
                    ->summarize(
                        Sum::make()
                            ->numeric()
                            ->visible(fn (Component $livewire) => ! empty($livewire->tableFilters['categories']['value']))
                            ->formatStateUsing(fn ($state) => round($state / 100, 2))
                    ),
                TextColumn::make('leftover')
                    ->alignRight()
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$state} {$expense->category?->unit}")
                    ->summarize(
                        Sum::make()
                            ->numeric()
                            ->visible(fn (Component $livewire) => ! empty($livewire->tableFilters['categories']['value']))
                            ->formatStateUsing(fn ($state) => round($state / 100, 2))
                    ),
                TextColumn::make('purchase_date')
                    ->date()
                    ->wrapHeader()
                    ->sortable(),
                TextColumn::make('usage_date')
                    ->date()
                    ->placeholder('TBD')
                    ->wrapHeader(),
                TextColumn::make('interval')
                    ->label('Interval (days/months)')
                    ->wrapHeader()
                    ->alignCenter()
                    ->formatStateUsing(function (Expense $expense) {
                        return "{$expense->interval} / ~{$expense->interval_months}";
                    })
                    ->sortable(),
                TextColumn::make('usage_per_day')
                    ->label('Usage/day')
                    ->alignRight()
                    ->formatStateUsing(fn ($state, Expense $expense) => "{$state} {$expense->category?->unit}")
                    ->sortable()
                    ->summarize(
                        Average::make()
                            ->visible(fn (Component $livewire) => ! empty($livewire->tableFilters['categories']['value']))
                            ->formatStateUsing(fn ($state) => round($state / 100, 2))
                    ),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categories')
                    ->label('Category')
                    ->relationship('category', titleAttribute: 'name'),

                Filter::make('usage_date')
                    ->schema([
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
            ->recordActions([
                Action::make('view_note')
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
                    ->modalWidth(Width::Medium)
                    ->color(Color::Zinc)->visible(fn (Expense $expense) => ! empty($expense->note)),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export to file')
                        ->exporter(ExpenseExporter::class),
                    DeleteBulkAction::make(),
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
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
