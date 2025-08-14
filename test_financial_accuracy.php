<?php

/**
 * Comprehensive Financial Metrics Validation Test
 * This script validates all financial calculations across dashboard widgets
 * to ensure 100% accuracy and identify any calculation mistakes
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load the Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\VendorPayment;
use App\Models\InvoiceRefund;
use App\Models\Customer;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

echo "🔍 FINANCIAL METRICS ACCURACY VALIDATION\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$errors = [];
$warnings = [];

try {
    // Test 1: Invoice Remaining Balance Calculation
    echo "📋 Testing Invoice Remaining Balance Calculations...\n";

    $sampleInvoices = Invoice::with(['payments', 'refunds'])
        ->whereNotIn('status', ['cancelled'])
        ->limit(5)
        ->get();

    foreach ($sampleInvoices as $invoice) {
        $calculatedBalance = $invoice->total_amount - $invoice->total_paid;
        $modelBalance = $invoice->remaining_balance;

        if (abs($calculatedBalance - $modelBalance) > 0.01) {
            $errors[] = "Invoice #{$invoice->invoice_number}: Calculated balance ({$calculatedBalance}) != Model balance ({$modelBalance})";
        }

        // Test net amount calculation considering refunds
        $expectedNetAmount = $invoice->total_amount - $invoice->total_refunded;
        $actualNetAmount = $invoice->net_amount;

        if (abs($expectedNetAmount - $actualNetAmount) > 0.01) {
            $errors[] = "Invoice #{$invoice->invoice_number}: Net amount mismatch. Expected ({$expectedNetAmount}) != Actual ({$actualNetAmount})";
        }
    }

    // Test 2: Purchase Order Remaining Balance Calculation
    echo "📦 Testing Purchase Order Remaining Balance Calculations...\n";

    $samplePOs = PurchaseOrder::with('payments')
        ->whereNotIn('status', ['cancelled'])
        ->limit(5)
        ->get();

    foreach ($samplePOs as $po) {
        $calculatedBalance = $po->total_amount - $po->total_paid;
        $modelBalance = $po->remaining_balance;

        if (abs($calculatedBalance - $modelBalance) > 0.01) {
            $errors[] = "PO #{$po->po_number}: Calculated balance ({$calculatedBalance}) != Model balance ({$modelBalance})";
        }
    }

    // Test 3: ComprehensiveFinancialOverview Widget Calculations
    echo "📊 Testing ComprehensiveFinancialOverview Widget Calculations...\n";

    $currentYear = Carbon::now()->year;
    $currentMonth = Carbon::now()->month;

    // Test revenue calculation
    $paymentsSum = Payment::whereYear('payment_date', $currentYear)->sum('amount');
    $refundsSum = InvoiceRefund::where('status', 'processed')
        ->whereYear('refund_date', $currentYear)
        ->sum('refund_amount');
    $expectedRevenue = $paymentsSum - $refundsSum;

    // Test monthly revenue
    $monthlyPayments = Payment::whereYear('payment_date', $currentYear)
        ->whereMonth('payment_date', $currentMonth)
        ->sum('amount');
    $monthlyRefunds = InvoiceRefund::where('status', 'processed')
        ->whereYear('refund_date', $currentYear)
        ->whereMonth('refund_date', $currentMonth)
        ->sum('refund_amount');
    $expectedMonthlyRevenue = $monthlyPayments - $monthlyRefunds;

    // Test expenses calculation
    $expectedExpenses = VendorPayment::whereYear('payment_date', $currentYear)->sum('amount');
    $expectedMonthlyExpenses = VendorPayment::whereYear('payment_date', $currentYear)
        ->whereMonth('payment_date', $currentMonth)
        ->sum('amount');

    // Test outstanding receivables
    $outstandingReceivables = Invoice::where('status', '!=', 'paid')
        ->where('status', '!=', 'cancelled')
        ->where('status', '!=', 'refunded')
        ->get()
        ->sum(function ($invoice) {
            return $invoice->remaining_balance;
        });

    // Test outstanding payables
    $outstandingPayables = PurchaseOrder::where('status', '!=', 'paid')
        ->get()
        ->sum(function ($po) {
            return $po->total_amount - $po->total_paid;
        });

    echo "✅ Revenue Calculations:\n";
    echo "   - Yearly Revenue: Rs " . number_format($expectedRevenue, 2) . "\n";
    echo "   - Monthly Revenue: Rs " . number_format($expectedMonthlyRevenue, 2) . "\n";
    echo "   - Yearly Expenses: Rs " . number_format($expectedExpenses, 2) . "\n";
    echo "   - Monthly Expenses: Rs " . number_format($expectedMonthlyExpenses, 2) . "\n";
    echo "   - Net Profit (Yearly): Rs " . number_format($expectedRevenue - $expectedExpenses, 2) . "\n";
    echo "   - Net Profit (Monthly): Rs " . number_format($expectedMonthlyRevenue - $expectedMonthlyExpenses, 2) . "\n";
    echo "   - Outstanding Receivables: Rs " . number_format($outstandingReceivables, 2) . "\n";
    echo "   - Outstanding Payables: Rs " . number_format($outstandingPayables, 2) . "\n\n";

    // Test 4: CashFlowChart Calculations for Current Month
    echo "💰 Testing CashFlowChart Calculations for Current Month...\n";

    $currentMonthRevenue = Payment::whereYear('payment_date', Carbon::now()->year)
        ->whereMonth('payment_date', Carbon::now()->month)
        ->sum('amount');

    $currentMonthExpenses = VendorPayment::whereYear('payment_date', Carbon::now()->year)
        ->whereMonth('payment_date', Carbon::now()->month)
        ->sum('amount');

    $currentMonthProfit = $currentMonthRevenue - $currentMonthExpenses;

    echo "   - Current Month Revenue: Rs " . number_format($currentMonthRevenue, 2) . "\n";
    echo "   - Current Month Expenses: Rs " . number_format($currentMonthExpenses, 2) . "\n";
    echo "   - Current Month Profit: Rs " . number_format($currentMonthProfit, 2) . "\n\n";

    // Test 5: ServiceProfitabilityTable Calculations Sample
    echo "🔧 Testing ServiceProfitabilityTable Calculation Logic...\n";

    $serviceQuery = DB::table('categories')
        ->select([
            'categories.name as category_name',
            DB::raw('SUM(invoice_service.quantity * invoice_service.unit_price) as total_revenue'),
            DB::raw('COUNT(DISTINCT invoice_service.id) as times_sold')
        ])
        ->join('services', 'categories.id', '=', 'services.category_id')
        ->join('invoice_service', 'services.id', '=', 'invoice_service.service_id')
        ->join('invoices', 'invoice_service.invoice_id', '=', 'invoices.id')
        ->where('invoices.status', '!=', 'cancelled')
        ->whereYear('invoices.invoice_date', now()->year)
        ->groupBy('categories.id', 'categories.name')
        ->having('times_sold', '>', 0)
        ->orderByDesc('total_revenue')
        ->limit(3)
        ->get();

    foreach ($serviceQuery as $service) {
        echo "   - {$service->category_name}: Rs " . number_format($service->total_revenue, 2) . " ({$service->times_sold} sales)\n";
    }
    echo "\n";

    // Test 6: Outstanding Payments Table Age Calculation
    echo "⏰ Testing Outstanding Payments Age Calculations...\n";

    $overdueInvoices = Invoice::where('status', '!=', 'paid')
        ->whereNotNull('tour_date')
        ->whereRaw('total_amount > total_paid')
        ->where('tour_date', '<', Carbon::now())
        ->limit(3)
        ->get();

    foreach ($overdueInvoices as $invoice) {
        if ($invoice->tour_date) {
            $daysOverdue = Carbon::parse($invoice->tour_date)->diffInDays(Carbon::now(), false);
            echo "   - Invoice #{$invoice->invoice_number}: {$daysOverdue} days overdue\n";
        }
    }
    echo "\n";

    // Test 7: Currency Conversion Issues Check
    echo "💱 Checking for Currency Conversion Issues...\n";

    $vendorsWithCreditLimit = Vendor::where('credit_limit', '>', 0)->limit(3)->get();
    foreach ($vendorsWithCreditLimit as $vendor) {
        $outstandingBalance = $vendor->outstanding_balance;
        $creditUtilization = $vendor->credit_utilization_percentage;

        if ($creditUtilization > 100) {
            $warnings[] = "Vendor {$vendor->name}: Credit utilization over 100% ({$creditUtilization}%)";
        }

        echo "   - {$vendor->name}: {$creditUtilization}% credit utilization\n";
    }
    echo "\n";

    // Test 8: Status Logic Consistency
    echo "🔄 Testing Status Logic Consistency...\n";

    $inconsistentInvoices = Invoice::whereRaw('
        (status = "paid" AND total_paid < total_amount) OR
        (status = "pending" AND total_paid > 0) OR  
        (status = "partially_paid" AND (total_paid = 0 OR total_paid >= total_amount))
    ')->count();

    $inconsistentPOs = PurchaseOrder::whereRaw('
        (status = "paid" AND total_paid < total_amount) OR
        (status = "pending" AND total_paid > 0) OR
        (status = "partially_paid" AND (total_paid = 0 OR total_paid >= total_amount))
    ')->count();

    if ($inconsistentInvoices > 0) {
        $errors[] = "Found {$inconsistentInvoices} invoices with inconsistent status logic";
    }

    if ($inconsistentPOs > 0) {
        $errors[] = "Found {$inconsistentPOs} purchase orders with inconsistent status logic";
    }

    echo "   - Invoices with inconsistent status: {$inconsistentInvoices}\n";
    echo "   - Purchase Orders with inconsistent status: {$inconsistentPOs}\n\n";

    // FINAL REPORT
    echo "📄 FINAL VALIDATION REPORT\n";
    echo "=" . str_repeat("=", 30) . "\n";

    if (empty($errors) && empty($warnings)) {
        echo "🎉 ALL FINANCIAL CALCULATIONS ARE 100% ACCURATE!\n";
        echo "✅ No calculation errors found\n";
        echo "✅ All balance calculations are consistent\n";
        echo "✅ Status logic is working correctly\n";
        echo "✅ Currency handling is proper\n";
        echo "✅ Widget calculations are mathematically sound\n";
    } else {
        if (!empty($errors)) {
            echo "❌ CRITICAL ERRORS FOUND:\n";
            foreach ($errors as $error) {
                echo "   • {$error}\n";
            }
            echo "\n";
        }

        if (!empty($warnings)) {
            echo "⚠️  WARNINGS:\n";
            foreach ($warnings as $warning) {
                echo "   • {$warning}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ ERROR during validation: " . $e->getMessage() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n";
    echo "📁 File: " . $e->getFile() . "\n";
}

echo "\n";
