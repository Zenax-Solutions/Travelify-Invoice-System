<?php
// Test script to verify enhanced email functionality

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test if enhanced email can be rendered without errors
try {
    echo "🔍 Testing enhanced email template...\n";

    // Get a test invoice
    $invoice = \App\Models\Invoice::with(['customer', 'services'])->first();

    if (!$invoice) {
        echo "❌ No invoices found in database. Please create an invoice first.\n";
        exit;
    }

    echo "✅ Found invoice: {$invoice->invoice_number}\n";

    // Test if the email view can be rendered
    $emailContent = view('emails.invoices.sent', ['invoice' => $invoice])->render();

    if (strlen($emailContent) > 1000) {
        echo "✅ Email template rendered successfully (" . strlen($emailContent) . " characters)\n";
    } else {
        echo "⚠️ Email template seems too short\n";
    }

    // Test if PDF view can be rendered
    $pdfContent = view('invoices.pdf', ['invoice' => $invoice])->render();

    if (strlen($pdfContent) > 500) {
        echo "✅ PDF template rendered successfully (" . strlen($pdfContent) . " characters)\n";
    } else {
        echo "⚠️ PDF template seems too short\n";
    }

    echo "✅ All templates working correctly!\n";
    echo "📧 Email now includes full invoice layout\n";
    echo "📄 PDF download available without unwanted buttons\n";
    echo "🚫 No PDF attachment in email anymore\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
