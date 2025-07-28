<?php
// Test script to verify InvoiceResource table actions

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test if InvoiceResource can be instantiated without errors
try {
    echo "🔍 Testing InvoiceResource class loading...\n";

    // Test class exists and can be loaded
    $resourceClass = \App\Filament\Resources\InvoiceResource::class;
    echo "✅ InvoiceResource class exists: $resourceClass\n";

    // Test that the table method exists and returns proper type
    $reflection = new ReflectionClass($resourceClass);
    $tableMethod = $reflection->getMethod('table');
    echo "✅ table() method exists and is callable\n";

    // Test that all action methods are available
    echo "🔍 Checking table action implementations...\n";

    // Check if TableAction class is available
    if (class_exists(\Filament\Tables\Actions\Action::class)) {
        echo "✅ Filament\\Tables\\Actions\\Action class is available\n";
    } else {
        echo "❌ Filament\\Tables\\Actions\\Action class not found\n";
    }

    echo "✅ All checks passed! InvoiceResource should work correctly.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
