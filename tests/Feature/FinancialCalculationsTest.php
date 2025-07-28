<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Service;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Payment;
use App\Models\VendorPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class FinancialCalculationsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $vendor;
    protected $service;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->category = Category::create([
            'name' => 'Test Category',
            'description' => 'Test category for testing'
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '1234567890',
            'address' => 'Test Address'
        ]);

        $this->vendor = Vendor::create([
            'name' => 'Test Vendor',
            'email' => 'vendor@test.com',
            'phone' => '0987654321',
            'address' => 'Vendor Address',
            'credit_limit' => 10000
        ]);

        $this->service = Service::create([
            'name' => 'Test Service',
            'category_id' => $this->category->id,
            'description' => 'Test service description',
            'price' => 100.00
        ]);
    }

    /** @test */
    public function it_calculates_invoice_totals_correctly()
    {
        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'tour_date' => now()->addDays(30),
            'status' => 'pending',
            'total_amount' => 0,
            'total_paid' => 0
        ]);

        // Add invoice items
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'quantity' => 2,
            'price' => 100.00,
            'total' => 200.00
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'quantity' => 1,
            'price' => 150.00,
            'total' => 150.00
        ]);

        // Update invoice totals
        $subtotal = $invoice->items->sum('total');
        $tax = $subtotal * 0.10; // 10% tax
        $total = $subtotal - $invoice->discount + $tax;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total
        ]);

        $invoice->refresh();

        $this->assertEquals(350.00, $invoice->subtotal);
        $this->assertEquals(35.00, $invoice->tax);
        $this->assertEquals(375.00, $invoice->total); // 350 - 10 + 35
    }

    /** @test */
    public function it_calculates_purchase_order_totals_correctly()
    {
        // Create purchase order
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => now(),
            'status' => 'pending',
            'total_amount' => 0,
            'total_paid' => 0
        ]);

        // Add purchase order items
        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'service_id' => $this->service->id,
            'quantity' => 3,
            'price' => 80.00,
            'total' => 240.00
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'service_id' => $this->service->id,
            'quantity' => 2,
            'price' => 120.00,
            'total' => 240.00
        ]);

        // Update purchase order totals
        $subtotal = $purchaseOrder->items->sum('total');
        $tax = $subtotal * 0.08; // 8% tax
        $total = $subtotal - $purchaseOrder->discount + $tax;

        $purchaseOrder->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total
        ]);

        $purchaseOrder->refresh();

        $this->assertEquals(480.00, $purchaseOrder->subtotal);
        $this->assertEquals(38.40, $purchaseOrder->tax);
        $this->assertEquals(513.40, $purchaseOrder->total); // 480 - 5 + 38.40
    }

    /** @test */
    public function it_calculates_vendor_credit_usage_correctly()
    {
        // Create purchase orders that use vendor credit
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => now(),
            'order_date' => now(),
            'delivery_date' => now()->addDays(7),
            'status' => 'completed',
            'total' => 2000.00
        ]);

        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-002',
            'vendor_id' => $this->vendor->id,
            'po_date' => now(),
            'order_date' => now(),
            'delivery_date' => now()->addDays(7),
            'status' => 'completed',
            'total' => 3000.00
        ]);

        // Create vendor payments
        VendorPayment::create([
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $po1->id,
            'amount' => 1500.00,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'status' => 'completed'
        ]);

        // Calculate remaining credit
        $totalOrders = $this->vendor->purchaseOrders()->sum('total');
        $totalPayments = $this->vendor->payments()->sum('amount');
        $usedCredit = $totalOrders - $totalPayments;
        $remainingCredit = $this->vendor->credit_limit - $usedCredit;

        $this->assertEquals(5000.00, $totalOrders);
        $this->assertEquals(1500.00, $totalPayments);
        $this->assertEquals(3500.00, $usedCredit);
        $this->assertEquals(6500.00, $remainingCredit);
    }

    /** @test */
    public function it_calculates_monthly_revenue_correctly()
    {
        $currentMonth = now()->startOfMonth();

        // Create invoices for current month
        $invoice1 = Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => $currentMonth->copy()->addDays(5),
            'issue_date' => $currentMonth->copy()->addDays(5),
            'due_date' => $currentMonth->copy()->addDays(35),
            'status' => 'paid',
            'total' => 1000.00
        ]);

        $invoice2 = Invoice::create([
            'invoice_number' => 'INV-002',
            'customer_id' => $this->customer->id,
            'invoice_date' => $currentMonth->copy()->addDays(10),
            'issue_date' => $currentMonth->copy()->addDays(10),
            'due_date' => $currentMonth->copy()->addDays(40),
            'status' => 'paid',
            'total' => 1500.00
        ]);

        // Create payment for invoices
        Payment::create([
            'invoice_id' => $invoice1->id,
            'amount' => 1000.00,
            'payment_date' => $currentMonth->copy()->addDays(6),
            'payment_method' => 'cash',
            'status' => 'completed'
        ]);

        Payment::create([
            'invoice_id' => $invoice2->id,
            'amount' => 1500.00,
            'payment_date' => $currentMonth->copy()->addDays(11),
            'payment_method' => 'bank_transfer',
            'status' => 'completed'
        ]);

        // Calculate monthly revenue
        $monthlyRevenue = Payment::whereYear('payment_date', $currentMonth->year)
            ->whereMonth('payment_date', $currentMonth->month)
            ->where('status', 'completed')
            ->sum('amount');

        $this->assertEquals(2500.00, $monthlyRevenue);
    }

    /** @test */
    public function it_calculates_monthly_expenses_correctly()
    {
        $currentMonth = now()->startOfMonth();

        // Create purchase orders for current month
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => $currentMonth->copy()->addDays(3),
            'order_date' => $currentMonth->copy()->addDays(3),
            'delivery_date' => $currentMonth->copy()->addDays(10),
            'status' => 'completed',
            'total' => 800.00
        ]);

        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-002',
            'vendor_id' => $this->vendor->id,
            'po_date' => $currentMonth->copy()->addDays(15),
            'order_date' => $currentMonth->copy()->addDays(15),
            'delivery_date' => $currentMonth->copy()->addDays(22),
            'status' => 'completed',
            'total' => 1200.00
        ]);

        // Create vendor payments
        VendorPayment::create([
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $po1->id,
            'amount' => 800.00,
            'payment_date' => $currentMonth->copy()->addDays(5),
            'payment_method' => 'bank_transfer',
            'status' => 'completed'
        ]);

        VendorPayment::create([
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $po2->id,
            'amount' => 1200.00,
            'payment_date' => $currentMonth->copy()->addDays(17),
            'payment_method' => 'cash',
            'status' => 'completed'
        ]);

        // Calculate monthly expenses
        $monthlyExpenses = VendorPayment::whereYear('payment_date', $currentMonth->year)
            ->whereMonth('payment_date', $currentMonth->month)
            ->where('status', 'completed')
            ->sum('amount');

        $this->assertEquals(2000.00, $monthlyExpenses);
    }

    /** @test */
    public function it_calculates_profit_margins_correctly()
    {
        // Create complete transaction cycle
        $currentMonth = now()->startOfMonth();

        // Revenue (Invoice)
        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => $currentMonth->copy()->addDays(5),
            'issue_date' => $currentMonth->copy()->addDays(5),
            'due_date' => $currentMonth->copy()->addDays(35),
            'status' => 'paid',
            'total' => 2000.00
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 2000.00,
            'payment_date' => $currentMonth->copy()->addDays(6),
            'payment_method' => 'bank_transfer',
            'status' => 'completed'
        ]);

        // Expense (Purchase Order)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => $currentMonth->copy()->addDays(1),
            'order_date' => $currentMonth->copy()->addDays(1),
            'delivery_date' => $currentMonth->copy()->addDays(5),
            'status' => 'completed',
            'total' => 1200.00
        ]);

        VendorPayment::create([
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $po->id,
            'amount' => 1200.00,
            'payment_date' => $currentMonth->copy()->addDays(3),
            'payment_method' => 'bank_transfer',
            'status' => 'completed'
        ]);

        // Calculate profit
        $revenue = Payment::where('status', 'completed')->sum('amount');
        $expenses = VendorPayment::where('status', 'completed')->sum('amount');
        $profit = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        $this->assertEquals(2000.00, $revenue);
        $this->assertEquals(1200.00, $expenses);
        $this->assertEquals(800.00, $profit);
        $this->assertEquals(40.00, $profitMargin);
    }

    /** @test */
    public function it_identifies_outstanding_payments_correctly()
    {
        $overdueDays = 30;
        $overdueDate = now()->subDays($overdueDays);

        // Create overdue invoice
        $overdueInvoice = Invoice::create([
            'invoice_number' => 'INV-OVERDUE',
            'customer_id' => $this->customer->id,
            'invoice_date' => $overdueDate->copy()->subDays(5),
            'issue_date' => $overdueDate->copy()->subDays(5),
            'due_date' => $overdueDate,
            'status' => 'pending',
            'total' => 1500.00
        ]);

        // Create current invoice
        $currentInvoice = Invoice::create([
            'invoice_number' => 'INV-CURRENT',
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->subDays(5),
            'issue_date' => now()->subDays(5),
            'due_date' => now()->addDays(25),
            'status' => 'pending',
            'total' => 1000.00
        ]);

        // Get outstanding invoices
        $outstandingInvoices = Invoice::where('status', '!=', 'paid')
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($invoice) {
                $daysOverdue = $invoice->due_date->isPast()
                    ? $invoice->due_date->diffInDays(now())
                    : 0;

                return [
                    'invoice' => $invoice,
                    'days_overdue' => $daysOverdue,
                    'is_overdue' => $daysOverdue > 0
                ];
            });

        $this->assertCount(2, $outstandingInvoices);

        $overdueItem = $outstandingInvoices->firstWhere('invoice.invoice_number', 'INV-OVERDUE');
        $currentItem = $outstandingInvoices->firstWhere('invoice.invoice_number', 'INV-CURRENT');

        $this->assertTrue($overdueItem['is_overdue']);
        $this->assertEquals(30, $overdueItem['days_overdue']);

        $this->assertFalse($currentItem['is_overdue']);
        $this->assertEquals(0, $currentItem['days_overdue']);
    }

    /** @test */
    public function it_calculates_service_profitability_correctly()
    {
        // Create service revenue (invoice items)
        $invoice = Invoice::create([
            'invoice_number' => 'INV-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now(),
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'paid',
            'total' => 500.00
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_id' => $this->service->id,
            'quantity' => 5,
            'price' => 100.00,
            'total' => 500.00
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed'
        ]);

        // Create service cost (purchase order items)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => now(),
            'order_date' => now(),
            'delivery_date' => now()->addDays(7),
            'status' => 'completed',
            'total' => 300.00
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'service_id' => $this->service->id,
            'quantity' => 5,
            'price' => 60.00,
            'total' => 300.00
        ]);

        VendorPayment::create([
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $po->id,
            'amount' => 300.00,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'status' => 'completed'
        ]);

        // Calculate service profitability
        $revenue = InvoiceItem::where('service_id', $this->service->id)
            ->whereHas('invoice.payments', function ($q) {
                $q->where('status', 'completed');
            })
            ->sum('total');

        $cost = PurchaseOrderItem::where('service_id', $this->service->id)
            ->whereHas('purchaseOrder.payments', function ($q) {
                $q->where('status', 'completed');
            })
            ->sum('total');

        $profit = $revenue - $cost;
        $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        $this->assertEquals(500.00, $revenue);
        $this->assertEquals(300.00, $cost);
        $this->assertEquals(200.00, $profit);
        $this->assertEquals(40.00, $profitMargin);
    }
}
