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
        $this->month = (string) Carbon::now()->month;
    }

    protected function getStats(): array
    {
        $invoiceQuery = Invoice::query();
        $invoicePaymentQuery = Payment::query();
        $purchaseOrderQuery = PurchaseOrder::query();
        $vendorPaymentQuery = VendorPayment::query();

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

        $totalYearlyInvoices = $invoiceQuery->count();
        $totalDailyInvoices = Invoice::whereDate('invoice_date', Carbon::today())->count();
        $todayPaidInvoiceTotal = Payment::whereDate('payment_date', Carbon::today())->sum('amount');

        $yearlyOutstandingInvoices = $invoiceQuery->get()->sum(function ($invoice) {
            return $invoice->remaining_balance;
        });
        $yearlyTotalInvoicePaid = $invoicePaymentQuery->sum('amount');

        $yearlyTotalPurchaseOrders = $purchaseOrderQuery->count();
        $yearlyTotalExpenses = $vendorPaymentQuery->sum('amount');
        $yearlyOutstandingPurchaseOrders = $purchaseOrderQuery->get()->sum(function ($po) {
            return $po->remaining_balance;
        });

        $netIncome = $yearlyTotalInvoicePaid - $yearlyTotalExpenses;

        return [
            Stat::make('Total Yearly Invoices', $totalYearlyInvoices)
                ->description('Invoices created this year')
                ->color('info'),
            Stat::make('Total Daily Invoices', $totalDailyInvoices)
                ->description('Invoices created today')
                ->color('info'),
            Stat::make('Today\'s Paid Amount (Invoices)', 'Rs ' . number_format($todayPaidInvoiceTotal, 2))
                ->description('Total payments received today from invoices')
                ->color('success'),
            Stat::make('Yearly Outstanding Amount (Invoices)', 'Rs ' . number_format($yearlyOutstandingInvoices, 2))
                ->description('Total amount still due this year from invoices')
                ->color('danger'),
            Stat::make('Yearly Total Paid (Invoices)', 'Rs ' . number_format($yearlyTotalInvoicePaid, 2))
                ->description('Total payments received this year from invoices')
                ->color('success'),
            Stat::make('Total Yearly Purchase Orders', $yearlyTotalPurchaseOrders)
                ->description('Purchase Orders created this year')
                ->color('info'),
            Stat::make('Yearly Total Expenses (PO Payments)', 'Rs ' . number_format($yearlyTotalExpenses, 2))
                ->description('Total payments made this year for purchase orders')
                ->color('danger'),
            Stat::make('Yearly Outstanding Amount (POs)', 'Rs ' . number_format($yearlyOutstandingPurchaseOrders, 2))
                ->description('Total amount still due this year for purchase orders')
                ->color('warning'),
            Stat::make('Net Income', 'Rs ' . number_format($netIncome, 2))
                ->description('Total income minus total expenses this year')
                ->color($netIncome >= 0 ? 'success' : 'danger'),
        ];
    }
}
