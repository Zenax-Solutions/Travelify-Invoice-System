<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\VendorPayment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverview extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Current month calculations
        $currentMonth = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        // Revenue (from all payments)
        $monthlyRevenue = Payment::whereBetween('payment_date', [$currentMonth, $currentMonthEnd])
            ->sum('amount');

        // Expenses (from all vendor payments)
        $monthlyExpenses = VendorPayment::whereBetween('payment_date', [$currentMonth, $currentMonthEnd])
            ->sum('amount');

        // Profit
        $monthlyProfit = $monthlyRevenue - $monthlyExpenses;

        // Outstanding invoices
        $outstandingAmount = Invoice::where('status', '!=', 'paid')
            ->sum('total_amount');

        return [
            Stat::make('Monthly Revenue', 'Rs. ' . number_format($monthlyRevenue, 2))
                ->description('Revenue for ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Monthly Expenses', 'Rs. ' . number_format($monthlyExpenses, 2))
                ->description('Expenses for ' . now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('Monthly Profit', 'Rs. ' . number_format($monthlyProfit, 2))
                ->description($monthlyProfit >= 0 ? 'Profit this month' : 'Loss this month')
                ->descriptionIcon($monthlyProfit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($monthlyProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Outstanding Amount', 'Rs. ' . number_format($outstandingAmount, 2))
                ->description('Pending invoice payments')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
