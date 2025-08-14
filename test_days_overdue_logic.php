<?php

/**
 * Test script to validate Days Overdue calculation in Outstanding Payments Table
 * This script tests the fixed logic for calculating overdue days
 */

require_once __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

echo "🔍 TESTING DAYS OVERDUE CALCULATION LOGIC\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Test function that mirrors the fixed logic
function calculateDaysOverdue($due_date, $tour_date, $invoice_date)
{
    // Priority: due_date > tour_date > invoice_date
    $compareDate = null;

    if ($due_date) {
        $compareDate = $due_date;
    } elseif ($tour_date) {
        $compareDate = $tour_date;
    } elseif ($invoice_date) {
        $compareDate = $invoice_date;
    }

    if (!$compareDate) {
        return 'N/A';
    }

    $today = Carbon::now()->startOfDay();
    $dueDate = Carbon::parse($compareDate)->startOfDay();

    // If today is after the due date, calculate overdue days
    if ($today->greaterThan($dueDate)) {
        return $dueDate->diffInDays($today);
    }

    // Not overdue yet
    return 0;
}

// Test cases
$testCases = [
    [
        'description' => 'Invoice overdue by 5 days (due_date)',
        'due_date' => Carbon::now()->subDays(5)->toDateString(),
        'tour_date' => null,
        'invoice_date' => Carbon::now()->subDays(10)->toDateString(),
        'expected' => 5
    ],
    [
        'description' => 'Invoice overdue by 15 days (tour_date fallback)',
        'due_date' => null,
        'tour_date' => Carbon::now()->subDays(15)->toDateString(),
        'invoice_date' => Carbon::now()->subDays(20)->toDateString(),
        'expected' => 15
    ],
    [
        'description' => 'Invoice overdue by 30 days (invoice_date fallback)',
        'due_date' => null,
        'tour_date' => null,
        'invoice_date' => Carbon::now()->subDays(30)->toDateString(),
        'expected' => 30
    ],
    [
        'description' => 'Invoice not overdue yet (future due date)',
        'due_date' => Carbon::now()->addDays(5)->toDateString(),
        'tour_date' => null,
        'invoice_date' => Carbon::now()->subDays(10)->toDateString(),
        'expected' => 0
    ],
    [
        'description' => 'Invoice due today',
        'due_date' => Carbon::now()->toDateString(),
        'tour_date' => null,
        'invoice_date' => Carbon::now()->subDays(5)->toDateString(),
        'expected' => 0
    ],
    [
        'description' => 'No dates available',
        'due_date' => null,
        'tour_date' => null,
        'invoice_date' => null,
        'expected' => 'N/A'
    ]
];

echo "📋 Running Test Cases...\n\n";

$passed = 0;
$failed = 0;

foreach ($testCases as $index => $testCase) {
    $result = calculateDaysOverdue(
        $testCase['due_date'],
        $testCase['tour_date'],
        $testCase['invoice_date']
    );

    $isPass = $result === $testCase['expected'];

    if ($isPass) {
        echo "✅ Test " . ($index + 1) . ": PASSED\n";
        $passed++;
    } else {
        echo "❌ Test " . ($index + 1) . ": FAILED\n";
        $failed++;
    }

    echo "   Description: {$testCase['description']}\n";
    echo "   Expected: {$testCase['expected']}, Got: {$result}\n";
    echo "   Due Date: " . ($testCase['due_date'] ?? 'null') . "\n";
    echo "   Tour Date: " . ($testCase['tour_date'] ?? 'null') . "\n";
    echo "   Invoice Date: " . ($testCase['invoice_date'] ?? 'null') . "\n\n";
}

echo "📊 TEST RESULTS:\n";
echo "✅ Passed: {$passed}\n";
echo "❌ Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n🎉 ALL TESTS PASSED! Days Overdue calculation is working correctly!\n";
    echo "✅ Logic correctly prioritizes due_date > tour_date > invoice_date\n";
    echo "✅ Properly calculates overdue days when date is in the past\n";
    echo "✅ Returns 0 for future dates or today\n";
    echo "✅ Handles missing dates with 'N/A'\n";
} else {
    echo "\n⚠️  Some tests failed. Check the logic above.\n";
}

echo "\n📋 EXPECTED BEHAVIOR IN DASHBOARD:\n";
echo "- Green badge (0): Invoice not overdue yet\n";
echo "- Yellow badge (1-7): Invoice overdue 1-7 days\n";
echo "- Red badge (8-30): Invoice overdue 8-30 days\n";
echo "- Red badge (30+): Invoice seriously overdue\n";
echo "- Gray badge (N/A): No dates available for calculation\n";

echo "\n";
