<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Service;
use App\Models\Category;
use Illuminate\Database\Seeder;

class PenaltySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create a test customer
        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '123456789',
            'address' => 'Test Address'
        ]);

        // Create a category
        $category = Category::create([
            'name' => 'Travel Services',
            'description' => 'Travel related services'
        ]);

        // Create a service
        $service = Service::create([
            'name' => 'Tour Package',
            'description' => 'Complete tour package',
            'category_id' => $category->id,
            'price' => 50000.00
        ]);

        // Get the admin user
        $user = User::first();

        // Create a test invoice
        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2025-001',
            'invoice_date' => now(),
            'tour_date' => now()->addDays(30),
            'subtotal' => 50000.00,
            'tax_amount' => 5000.00,
            'total_amount' => 55000.00,
            'total_paid' => 0.00,
            'status' => 'pending',
            'created_by' => $user->id ?? 1
        ]);

        // Attach service to invoice
        $invoice->services()->attach($service->id, [
            'quantity' => 1,
            'price' => 50000.00,
            'total' => 50000.00
        ]);

        echo "Test data created successfully!\n";
        echo "Customer: {$customer->name} (ID: {$customer->id})\n";
        echo "Invoice: {$invoice->invoice_number} (ID: {$invoice->id})\n";
    }
}
