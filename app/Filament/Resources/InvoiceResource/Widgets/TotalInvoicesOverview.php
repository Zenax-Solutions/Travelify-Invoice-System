<?php

namespace App\Filament\Resources\InvoiceResource\Widgets;

use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalInvoicesOverview extends BaseWidget
{

    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Invoices', Invoice::count()),
            Stat::make('Total Outstanding Amount', 'Rs ' . number_format(Invoice::sum('total_amount') - Invoice::sum('total_paid'), 2)),
            Stat::make('Total Paid Amount', 'Rs ' . number_format(Invoice::sum('total_paid'), 2))
                ->color('success'),
            Stat::make('Total Invoices This Month', Invoice::whereMonth('invoice_date', now()->month)
                ->count())
                ->description('Invoices created this month')
                ->color('info'),
            Stat::make('Total Invoices This Year', Invoice::whereYear('invoice_date', now()->year)
                ->count())
                ->description('Invoices created this year')
                ->color('info'),
            Stat::make('Total Invoices Today', Invoice::whereDate('invoice_date', now()->toDateString())
                ->count())
                ->description('Invoices created today')
                ->color('info'),
            Stat::make('Total Paid Invoices', Invoice::where('total_paid', '>', 0)
                ->count())
                ->description('Invoices that have been paid')
                ->color('success'),
            Stat::make('Total Invoices Cancelled', Invoice::where('status', 'cancelled')
                ->count())
                ->description('Invoices that have been cancelled')
                ->color('secondary'),
            Stat::make('Total Invoices Draft', Invoice::where('status', 'draft')
                ->count())
        ];
    }
}
