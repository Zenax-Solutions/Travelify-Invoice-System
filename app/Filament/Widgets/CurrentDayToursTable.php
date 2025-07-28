<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class CurrentDayToursTable extends BaseWidget
{
    protected static ?string $heading = 'Today\'s Tours';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->whereDate('tour_date', now()->toDateString())
                    ->orderBy('tour_date')
            )
            ->emptyStateHeading('No Tours Today')
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tour_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->money('LKR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        'partially_paid' => 'info',
                    }),
            ]);
    }
}
