<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Carbon\Carbon;

class OutstandingPaymentsTable extends BaseWidget
{
    protected static ?string $heading = 'Outstanding Payments Overview';
    protected static ?string $description = 'Urgent payments requiring attention';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->select([
                        'invoices.*',
                        'customers.name as customer_name',
                        'customers.email as customer_email',
                    ])
                    ->join('customers', 'invoices.customer_id', '=', 'customers.id')
                    ->where('invoices.status', '!=', 'paid')
                    ->whereRaw('invoices.total_amount > invoices.total_paid')
                    ->orderBy('invoices.invoice_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tour_date')
                    ->label('Tour Date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('LKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Paid Amount')
                    ->money('LKR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_balance')
                    ->label('Outstanding')
                    ->money('LKR')
                    ->color('danger')
                    ->weight('bold')
                    ->getStateUsing(function ($record) {
                        return $record->total_amount - $record->total_paid;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(function ($record) {
                        if (!$record->tour_date) {
                            return 'N/A';
                        }
                        $daysOverdue = Carbon::parse($record->tour_date)->diffInDays(Carbon::now(), false);
                        return $daysOverdue > 0 ? $daysOverdue : 0;
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state === 'N/A') return 'gray';
                        if ($state == 0) return 'success';
                        if ($state <= 7) return 'warning';
                        if ($state <= 30) return 'danger';
                        return 'gray';
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'pending' => 'warning',
                            'partially_paid' => 'info',
                            'paid' => 'success',
                            'cancelled' => 'danger',
                            default => 'gray',
                        };
                    }),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->paginated([10, 25, 50])
            ->poll('60s');
    }
}
