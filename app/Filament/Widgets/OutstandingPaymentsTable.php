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
                    ->where('invoices.status', '!=', 'cancelled')
                    ->where('invoices.status', '!=', 'refunded')
                    ->whereRaw('(invoices.total_amount + COALESCE(invoices.total_penalties, 0) - COALESCE(invoices.total_refunded, 0)) > invoices.total_paid')
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
                        // Use effective amount (includes penalties)
                        $effectiveAmount = $record->total_amount - $record->total_refunded + ($record->total_penalties ?? 0);
                        return $effectiveAmount - $record->total_paid;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_overdue')
                    ->label('Days Overdue')
                    ->getStateUsing(function ($record) {
                        // Priority: due_date > tour_date > invoice_date
                        $compareDate = null;

                        if ($record->due_date) {
                            $compareDate = $record->due_date;
                        } elseif ($record->tour_date) {
                            $compareDate = $record->tour_date;
                        } elseif ($record->invoice_date) {
                            $compareDate = $record->invoice_date;
                        }

                        if (!$compareDate) {
                            return 'N/A';
                        }

                        $today = Carbon::now()->startOfDay();
                        $dueDate = Carbon::parse($compareDate)->startOfDay();

                        // If today is after the due date, calculate overdue days
                        if ($today->greaterThan($dueDate)) {
                            return $dueDate->diffInDays($today);
                        }

                        // Not overdue yet
                        return 0;
                    })
                    ->badge()
                    ->color(function ($state) {
                        if ($state === 'N/A') return 'gray';
                        if ($state == 0) return 'success';
                        if ($state <= 7) return 'warning';
                        if ($state <= 30) return 'danger';
                        return 'danger';
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
