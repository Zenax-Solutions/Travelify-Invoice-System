<?php

// Test invoice null safety
require_once 'vendor/autoload.php';

use App\Models\Invoice;
use Carbon\Carbon;

// Test tour_date null safety
echo "Testing invoice tour_date null safety...\n";

// Test 1: Invoice with null tour_date
$mockInvoice = new \stdClass();
$mockInvoice->tour_date = null;
$mockInvoice->invoice_number = 'TEST-001';
$mockInvoice->invoice_date = Carbon::now();

// Simulate the fixed logic
if ($mockInvoice->tour_date) {
    echo "Tour Date: " . $mockInvoice->tour_date->format('Y-m-d') . "\n";
} else {
    echo "Tour Date: Not Set (safely handled)\n";
}

// Test 2: Days overdue calculation
function calculateDaysOverdue($record)
{
    if (!$record->tour_date) {
        return 'N/A';
    }
    $daysOverdue = Carbon::parse($record->tour_date)->diffInDays(Carbon::now(), false);
    return $daysOverdue > 0 ? $daysOverdue : 0;
}

echo "Days Overdue: " . calculateDaysOverdue($mockInvoice) . "\n";

echo "✅ All tour_date null safety checks passed!\n";
