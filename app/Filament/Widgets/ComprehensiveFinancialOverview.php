<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\InvoiceRefund;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\VendorPayment;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Penalty;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ComprehensiveFinancialOverview extends BaseWidget
{
    protected static string $view = 'filament.widgets.comprehensive-financial-overview';
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;
    protected static ?int $sort = 1;

    public ?string $year = null;
    public ?string $month = null;

    public function mount(): void
    {
        $this->year = (string) Carbon::now()->year;
        $this->month = (string) Carbon::now()->month;
    }

    public function updatedYear(): void
    {
        // Reset month when year changes to avoid invalid combinations
        $this->month = (string) Carbon::now()->month;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $currentMonth = Carbon::now();
        $currentYear = Carbon::now();

        // REVENUE METRICS
        $totalRevenue = $this->calculateTotalRevenue();
        $monthlyRevenue = $this->calculateMonthlyRevenue();
        $dailyRevenue = $this->calculateDailyRevenue();

        // EXPENSE METRICS
        $totalExpenses = $this->calculateTotalExpenses();
        $monthlyExpenses = $this->calculateMonthlyExpenses();

        // PROFIT METRICS
        $netProfit = $totalRevenue - $totalExpenses;
        $monthlyProfit = $monthlyRevenue - $monthlyExpenses;
        $profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

        // REFUND AND CANCELLATION METRICS
        $totalRefunds = $this->calculateTotalRefunds();
        $monthlyRefunds = $this->calculateMonthlyRefunds();
        $cancelledInvoices = $this->calculateCancelledInvoices();

        // PENALTY METRICS
        $totalPenalties = $this->calculateTotalPenalties();
        $monthlyPenalties = $this->calculateMonthlyPenalties();
        $agencyAbsorbedPenalties = $this->calculateAgencyAbsorbedPenalties();

        // OUTSTANDING METRICS
        $outstandingReceivables = $this->calculateOutstandingReceivables();
        $outstandingPayables = $this->calculateOutstandingPayables();
        $netCashFlow = $outstandingReceivables - $outstandingPayables;

        // CUSTOMER METRICS
        $totalCustomers = Customer::count();
        $newCustomersThisMonth = Customer::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)->count();

        // VENDOR METRICS
        $activeVendors = Vendor::whereHas('purchaseOrders')->count();
        $vendorsOverCreditLimit = Vendor::where('credit_limit', '>', 0)
            ->get()->filter(fn($vendor) => $vendor->is_over_credit_limit)->count();

        return [
            // REVENUE SECTION
            Stat::make('Total Revenue (Yearly)', 'Rs ' . number_format($totalRevenue, 2))
                ->description('Total payments received this year')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Monthly Revenue', 'Rs ' . number_format($monthlyRevenue, 2))
                ->description('Revenue for current month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            Stat::make('Today\'s Revenue', 'Rs ' . number_format($dailyRevenue, 2))
                ->description('Payments received today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            // REFUND SECTION
            Stat::make('Total Refunds (Yearly)', 'Rs ' . number_format($totalRefunds, 2))
                ->description('Total refunds processed this year')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('Monthly Refunds', 'Rs ' . number_format($monthlyRefunds, 2))
                ->description('Refunds processed this month')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('warning'),

            Stat::make('Cancelled Invoices', number_format($cancelledInvoices))
                ->description('Invoices cancelled this year')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            // PENALTY SECTION
            Stat::make('Total Penalties (Yearly)', 'Rs ' . number_format($totalPenalties, 2))
                ->description('Total penalties applied this year')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Monthly Penalties', 'Rs ' . number_format($monthlyPenalties, 2))
                ->description('Penalties applied this month')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Agency Absorbed', 'Rs ' . number_format($agencyAbsorbedPenalties, 2))
                ->description('Penalties absorbed by agency this year')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('danger'),

            // EXPENSE SECTION
            Stat::make('Total Expenses (Yearly)', 'Rs ' . number_format($totalExpenses, 2))
                ->description('Total vendor + penalty costs this year')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Monthly Expenses', 'Rs ' . number_format($monthlyExpenses, 2))
                ->description('Expenses for current month')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('danger'),

            // PROFIT SECTION
            Stat::make('Net Profit (Yearly)', 'Rs ' . number_format($netProfit, 2))
                ->description('Revenue minus expenses')
                ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-chart-bar' : 'heroicon-m-exclamation-triangle')
                ->color($netProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Monthly Profit', 'Rs ' . number_format($monthlyProfit, 2))
                ->description('Current month profit/loss')
                ->descriptionIcon($monthlyProfit >= 0 ? 'heroicon-m-chart-bar' : 'heroicon-m-exclamation-triangle')
                ->color($monthlyProfit >= 0 ? 'success' : 'danger'),

            Stat::make('Profit Margin', number_format($profitMargin, 1) . '%')
                ->description('Overall profit percentage')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($profitMargin >= 20 ? 'success' : ($profitMargin >= 10 ? 'warning' : 'danger')),

            // CASH FLOW SECTION
            Stat::make('Outstanding Receivables', 'Rs ' . number_format($outstandingReceivables, 2))
                ->description('Money owed by customers')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Outstanding Payables', 'Rs ' . number_format($outstandingPayables, 2))
                ->description('Money owed to vendors')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('danger'),

            Stat::make('Net Cash Position', 'Rs ' . number_format($netCashFlow, 2))
                ->description('Receivables minus payables')
                ->descriptionIcon($netCashFlow >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                ->color($netCashFlow >= 0 ? 'success' : 'warning'),

            // BUSINESS METRICS
            Stat::make('Total Customers', number_format($totalCustomers))
                ->description('All registered customers')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('New Customers (Monthly)', number_format($newCustomersThisMonth))
                ->description('New customers this month')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('info'),

            Stat::make('Active Vendors', number_format($activeVendors))
                ->description('Vendors with purchase orders')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make('Credit Limit Alerts', number_format($vendorsOverCreditLimit))
                ->description('Vendors over credit limit')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($vendorsOverCreditLimit > 0 ? 'danger' : 'success'),
        ];
    }

    private function calculateTotalRevenue(): float
    {
        $payments = Payment::whereYear('payment_date', $this->year ?? Carbon::now()->year)
            ->sum('amount');

        $refunds = InvoiceRefund::where('status', 'processed')
            ->whereYear('refund_date', $this->year ?? Carbon::now()->year)
            ->sum('refund_amount');

        // Add customer-paid penalties as revenue
        $customerPenalties = Penalty::where('status', 'applied')
            ->whereYear('penalty_date', $this->year ?? Carbon::now()->year)
            ->sum('customer_amount');

        return $payments - $refunds + $customerPenalties;
    }

    private function calculateMonthlyRevenue(): float
    {
        $payments = Payment::whereYear('payment_date', $this->year ?? Carbon::now()->year)
            ->whereMonth('payment_date', $this->month ?? Carbon::now()->month)
            ->sum('amount');

        $refunds = InvoiceRefund::where('status', 'processed')
            ->whereYear('refund_date', $this->year ?? Carbon::now()->year)
            ->whereMonth('refund_date', $this->month ?? Carbon::now()->month)
            ->sum('refund_amount');

        // Add customer-paid penalties as revenue
        $customerPenalties = Penalty::where('status', 'applied')
            ->whereYear('penalty_date', $this->year ?? Carbon::now()->year)
            ->whereMonth('penalty_date', $this->month ?? Carbon::now()->month)
            ->sum('customer_amount');

        return $payments - $refunds + $customerPenalties;
    }

    private function calculateDailyRevenue(): float
    {
        $payments = Payment::whereDate('payment_date', Carbon::today())->sum('amount');

        $refunds = InvoiceRefund::where('status', 'processed')
            ->whereDate('refund_date', Carbon::today())
            ->sum('refund_amount');

        // Add customer-paid penalties as revenue
        $customerPenalties = Penalty::where('status', 'applied')
            ->whereDate('penalty_date', Carbon::today())
            ->sum('customer_amount');

        return $payments - $refunds + $customerPenalties;
    }

    private function calculateTotalExpenses(): float
    {
        $vendorPayments = VendorPayment::whereYear('payment_date', $this->year ?? Carbon::now()->year)
            ->sum('amount');

        // Add agency-absorbed penalties as expenses
        $agencyPenalties = Penalty::where('status', '!=', 'waived')
            ->whereYear('penalty_date', $this->year ?? Carbon::now()->year)
            ->sum('agency_amount');

        return $vendorPayments + $agencyPenalties;
    }

    private function calculateMonthlyExpenses(): float
    {
        $vendorPayments = VendorPayment::whereYear('payment_date', $this->year ?? Carbon::now()->year)
            ->whereMonth('payment_date', $this->month ?? Carbon::now()->month)
            ->sum('amount');

        // Add agency-absorbed penalties as expenses
        $agencyPenalties = Penalty::where('status', '!=', 'waived')
            ->whereYear('penalty_date', $this->year ?? Carbon::now()->year)
            ->whereMonth('penalty_date', $this->month ?? Carbon::now()->month)
            ->sum('agency_amount');

        return $vendorPayments + $agencyPenalties;
    }

    private function calculateOutstandingReceivables(): float
    {
        return Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'refunded')
            ->get()
            ->sum(function ($invoice) {
                return $invoice->remaining_balance;
            });
    }

    private function calculateOutstandingPayables(): float
    {
        return PurchaseOrder::where('status', '!=', 'paid')
            ->get()
            ->sum(function ($po) {
                return $po->total_amount - $po->total_paid;
            });
    }

    // REFUND CALCULATION METHODS
    private function calculateTotalRefunds(): float
    {
        return InvoiceRefund::where('status', 'processed')
            ->whereYear('refund_date', $this->year ?? Carbon::now()->year)
            ->sum('refund_amount');
    }

    private function calculateMonthlyRefunds(): float
    {
        return InvoiceRefund::where('status', 'processed')
            ->whereYear('refund_date', $this->year ?? Carbon::now()->year)
            ->whereMonth('refund_date', $this->month ?? Carbon::now()->month)
            ->sum('refund_amount');
    }

    private function calculateCancelledInvoices(): int
    {
        return Invoice::where('status', 'cancelled')
            ->whereYear('cancelled_at', $this->year ?? Carbon::now()->year)
            ->count();
    }

    // PENALTY CALCULATION METHODS
    private function calculateTotalPenalties(): float
    {
        return Penalty::where('status', 'applied')
            ->whereYear('penalty_date', $this->year ?? Carbon::now()->year)
            ->sum('customer_amount');
    }

    private function calculateMonthlyPenalties(): float
    {
        return Penalty::where('status', 'applied')
            ->whereYear('penalty_date', $this->year ?? Carbon::now()->year)
            ->whereMonth('penalty_date', $this->month ?? Carbon::now()->month)
            ->sum('customer_amount');
    }

    private function calculateAgencyAbsorbedPenalties(): float
    {
        return Penalty::where('status', '!=', 'waived')
            ->whereYear('penalty_date', $this->year ?? Carbon::now()->year)
            ->sum('agency_amount');
    }
}
