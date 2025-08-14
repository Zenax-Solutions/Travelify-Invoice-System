<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\VendorPayment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OriginalFinancialOverview extends BaseWidget
{
    protected static string $view = 'filament.widgets.financial-overview';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = null;


    public ?string $year = null;
    public ?string $month = null;

    public function mount(): void
    {
        $this->year = (string) Carbon::now()->year;
        $this->month = null;
    }

    protected function getStats(): array
    {
        $invoiceQuery = Invoice::query();
        $invoicePaymentQuery = Payment::query();
        $purchaseOrderQuery = PurchaseOrder::query();
        $vendorPaymentQuery = VendorPayment::query();

        // Determine period labels based on filters
        $periodLabel = $this->month ? Carbon::createFromFormat('m', $this->month)->format('F') . ' ' . $this->year : $this->year;
        $isMonthlyView = !empty($this->month);

        if ($this->year) {
            $invoiceQuery->whereYear('invoice_date', $this->year);
            $invoicePaymentQuery->whereYear('payment_date', $this->year);
            $purchaseOrderQuery->whereYear('po_date', $this->year);
            $vendorPaymentQuery->whereYear('payment_date', $this->year);
        }

        if ($this->month) {
            $invoiceQuery->whereMonth('invoice_date', $this->month);
            $invoicePaymentQuery->whereMonth('payment_date', $this->month);
            $purchaseOrderQuery->whereMonth('po_date', $this->month);
            $vendorPaymentQuery->whereMonth('payment_date', $this->month);
        }

        $totalPeriodInvoices = $invoiceQuery->count();

        // Daily stats - always show today's data regardless of filters
        $totalDailyInvoices = Invoice::whereDate('invoice_date', Carbon::today())->count();
        $todayPaidInvoiceTotal = Payment::whereDate('payment_date', Carbon::today())->sum('amount');

        // Period-based calculations
        $periodOutstandingInvoices = $invoiceQuery->get()->sum(function ($invoice) {
            return $invoice->remaining_balance;
        });
        $periodTotalInvoicePaid = $invoicePaymentQuery->sum('amount');

        $periodTotalPurchaseOrders = $purchaseOrderQuery->count();
        $periodTotalExpenses = $vendorPaymentQuery->sum('amount');
        $periodOutstandingPurchaseOrders = $purchaseOrderQuery->get()->sum(function ($po) {
            return $po->remaining_balance;
        });

        $netIncome = $periodTotalInvoicePaid - $periodTotalExpenses;

        return [
            Stat::make($isMonthlyView ? "Total Invoices ($periodLabel)" : "Total Yearly Invoices", $totalPeriodInvoices)
                ->description($isMonthlyView ? "Invoices created in $periodLabel" : "Invoices created this year")
                ->color('info'),
            Stat::make('Total Daily Invoices', $totalDailyInvoices)
                ->description('Invoices created today (' . Carbon::today()->format('M d, Y') . ')')
                ->color('info'),
            Stat::make('Today\'s Paid Amount', 'Rs ' . number_format($todayPaidInvoiceTotal, 2))
                ->description('Total payments received today')
                ->color('success'),
            Stat::make($isMonthlyView ? "Outstanding Amount ($periodLabel)" : "Yearly Outstanding Amount (Invoices)", 'Rs ' . number_format($periodOutstandingInvoices, 2))
                ->description($isMonthlyView ? "Amount still due for $periodLabel" : "Total amount still due this year from invoices")
                ->color('danger'),
            Stat::make($isMonthlyView ? "Total Paid ($periodLabel)" : "Yearly Total Paid (Invoices)", 'Rs ' . number_format($periodTotalInvoicePaid, 2))
                ->description($isMonthlyView ? "Payments received in $periodLabel" : "Total payments received this year from invoices")
                ->color('success'),
            Stat::make($isMonthlyView ? "Purchase Orders ($periodLabel)" : "Total Yearly Purchase Orders", $periodTotalPurchaseOrders)
                ->description($isMonthlyView ? "Purchase Orders created in $periodLabel" : "Purchase Orders created this year")
                ->color('info'),
            Stat::make($isMonthlyView ? "Total Expenses ($periodLabel)" : "Yearly Total Expenses (PO Payments)", 'Rs ' . number_format($periodTotalExpenses, 2))
                ->description($isMonthlyView ? "Payments made in $periodLabel" : "Total payments made this year for purchase orders")
                ->color('danger'),
            Stat::make($isMonthlyView ? "Outstanding POs ($periodLabel)" : "Yearly Outstanding Amount (POs)", 'Rs ' . number_format($periodOutstandingPurchaseOrders, 2))
                ->description($isMonthlyView ? "Amount still due for POs in $periodLabel" : "Total amount still due this year for purchase orders")
                ->color('warning'),
            Stat::make($isMonthlyView ? "Net Income ($periodLabel)" : "Net Income (Yearly)", 'Rs ' . number_format($netIncome, 2))
                ->description($isMonthlyView ? "Income minus expenses for $periodLabel" : "Total income minus total expenses this year")
                ->color($netIncome >= 0 ? 'success' : 'danger'),
        ];
    }
}
