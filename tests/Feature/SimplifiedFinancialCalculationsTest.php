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

class SimplifiedFinancialCalculationsTest extends TestCase
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
    public function it_can_create_and_calculate_basic_invoice()
    {
        // Create invoice with basic structure
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'tour_date' => now()->addDays(30),
            'status' => 'pending',
            'total_amount' => 1000.00,
            'total_paid' => 0.00
        ]);

        $this->assertDatabaseHas('invoices', [
            'invoice_number' => 'INV-TEST-001',
            'total_amount' => 1000.00,
            'status' => 'pending'
        ]);

        $this->assertEquals(1000.00, $invoice->total_amount);
        $this->assertEquals('pending', $invoice->status);
    }

    /** @test */
    public function it_can_create_and_calculate_basic_purchase_order()
    {
        // Create purchase order with basic structure
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => now(),
            'status' => 'pending',
            'total_amount' => 800.00,
            'total_paid' => 0.00
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'po_number' => 'PO-TEST-001',
            'total_amount' => 800.00,
            'status' => 'pending'
        ]);

        $this->assertEquals(800.00, $purchaseOrder->total_amount);
        $this->assertEquals('pending', $purchaseOrder->status);
    }

    /** @test */
    public function it_calculates_monthly_revenue_from_payments()
    {
        $currentMonth = now()->startOfMonth();

        // Create invoice
        $invoice = Invoice::create([
            'invoice_number' => 'INV-REV-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => $currentMonth->copy()->addDays(5),
            'due_date' => $currentMonth->copy()->addDays(35),
            'tour_date' => $currentMonth->copy()->addDays(35),
            'status' => 'paid',
            'total_amount' => 2000.00,
            'total_paid' => 2000.00
        ]);

        // Create payment
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 2000.00,
            'payment_date' => $currentMonth->copy()->addDays(6),
            'payment_method' => 'cash'
        ]);

        // Test calculation
        $monthlyRevenue = Payment::whereYear('payment_date', $currentMonth->year)
            ->whereMonth('payment_date', $currentMonth->month)
            ->sum('amount');

        $this->assertEquals(2000.00, $monthlyRevenue);
    }

    /** @test */
    public function it_calculates_monthly_expenses_from_vendor_payments()
    {
        $currentMonth = now()->startOfMonth();

        // Create purchase order
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-EXP-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => $currentMonth->copy()->addDays(3),
            'status' => 'completed',
            'total_amount' => 1500.00,
            'total_paid' => 1500.00
        ]);

        // Create vendor payment
        $vendorPayment = VendorPayment::create([
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 1500.00,
            'payment_date' => $currentMonth->copy()->addDays(5),
            'payment_method' => 'bank_transfer'
        ]);

        // Test calculation
        $monthlyExpenses = VendorPayment::whereYear('payment_date', $currentMonth->year)
            ->whereMonth('payment_date', $currentMonth->month)
            ->sum('amount');

        $this->assertEquals(1500.00, $monthlyExpenses);
    }

    /** @test */
    public function it_calculates_profit_correctly()
    {
        $currentMonth = now()->startOfMonth();

        // Create revenue scenario
        $invoice = Invoice::create([
            'invoice_number' => 'INV-PROFIT-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => $currentMonth->copy()->addDays(5),
            'due_date' => $currentMonth->copy()->addDays(35),
            'tour_date' => $currentMonth->copy()->addDays(35),
            'status' => 'paid',
            'total_amount' => 3000.00,
            'total_paid' => 3000.00
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 3000.00,
            'payment_date' => $currentMonth->copy()->addDays(6),
            'payment_method' => 'bank_transfer'
        ]);

        // Create expense scenario
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-PROFIT-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => $currentMonth->copy()->addDays(1),
            'status' => 'completed',
            'total_amount' => 1800.00,
            'total_paid' => 1800.00
        ]);

        VendorPayment::create([
            'purchase_order_id' => $purchaseOrder->id,
            'amount' => 1800.00,
            'payment_date' => $currentMonth->copy()->addDays(3),
            'payment_method' => 'bank_transfer'
        ]);

        // Calculate profit
        $revenue = Payment::sum('amount');
        $expenses = VendorPayment::sum('amount');
        $profit = $revenue - $expenses;
        $profitMargin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

        $this->assertEquals(3000.00, $revenue);
        $this->assertEquals(1800.00, $expenses);
        $this->assertEquals(1200.00, $profit);
        $this->assertEquals(40.00, $profitMargin);
    }

    /** @test */
    public function it_identifies_outstanding_invoices()
    {
        $overdueDays = 30;
        $overdueDate = now()->subDays($overdueDays);

        // Create overdue invoice
        $overdueInvoice = Invoice::create([
            'invoice_number' => 'INV-OVERDUE-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => $overdueDate->copy()->subDays(5),
            'due_date' => $overdueDate,
            'tour_date' => $overdueDate->copy()->addDays(5),
            'status' => 'pending',
            'total_amount' => 2500.00,
            'total_paid' => 0.00
        ]);

        // Create current invoice
        $currentInvoice = Invoice::create([
            'invoice_number' => 'INV-CURRENT-001',
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->subDays(5),
            'due_date' => now()->addDays(25),
            'tour_date' => now()->addDays(30),
            'status' => 'pending',
            'total_amount' => 1200.00,
            'total_paid' => 0.00
        ]);

        // Test outstanding calculations
        $outstandingAmount = Invoice::where('status', '!=', 'paid')->sum('total_amount');
        $overdueAmount = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->sum('total_amount');

        $this->assertEquals(3700.00, $outstandingAmount); // 2500 + 1200
        $this->assertEquals(2500.00, $overdueAmount); // Only the overdue one
    }

    /** @test */
    public function it_calculates_vendor_credit_usage()
    {
        // Create multiple purchase orders for the vendor
        $po1 = PurchaseOrder::create([
            'po_number' => 'PO-CREDIT-001',
            'vendor_id' => $this->vendor->id,
            'po_date' => now(),
            'status' => 'completed',
            'total_amount' => 4000.00,
            'total_paid' => 2000.00 // Partially paid
        ]);

        $po2 = PurchaseOrder::create([
            'po_number' => 'PO-CREDIT-002',
            'vendor_id' => $this->vendor->id,
            'po_date' => now(),
            'status' => 'completed',
            'total_amount' => 3000.00,
            'total_paid' => 3000.00 // Fully paid
        ]);

        // Create payments
        VendorPayment::create([
            'purchase_order_id' => $po1->id,
            'amount' => 2000.00,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer'
        ]);

        VendorPayment::create([
            'purchase_order_id' => $po2->id,
            'amount' => 3000.00,
            'payment_date' => now(),
            'payment_method' => 'cash'
        ]);

        // Calculate credit usage through purchase orders relationship
        $totalOrders = $this->vendor->purchaseOrders()->sum('total_amount');
        $totalPayments = VendorPayment::whereHas('purchaseOrder', function ($query) {
            $query->where('vendor_id', $this->vendor->id);
        })->sum('amount');
        $usedCredit = $totalOrders - $totalPayments;
        $remainingCredit = $this->vendor->credit_limit - $usedCredit;

        $this->assertEquals(7000.00, $totalOrders);
        $this->assertEquals(5000.00, $totalPayments);
        $this->assertEquals(2000.00, $usedCredit);
        $this->assertEquals(8000.00, $remainingCredit);
    }
}
