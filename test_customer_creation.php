<?php
// Simple test script to verify customer creation works

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test customer creation
try {
    $customer = \App\Models\Customer::create([
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '+94123456789',
        'address' => 'Test Address'
    ]);

    echo "✅ Customer created successfully!\n";
    echo "ID: {$customer->id}\n";
    echo "Name: {$customer->name}\n";
    echo "Created By: {$customer->created_by}\n";
    echo "Updated By: {$customer->updated_by}\n";

    // Clean up
    $customer->delete();
    echo "✅ Test customer deleted\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
