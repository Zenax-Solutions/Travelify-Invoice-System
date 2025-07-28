<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles for the travel agency system
        $manager = Role::firstOrCreate(['name' => 'Travel Manager']);
        $accountant = Role::firstOrCreate(['name' => 'Financial Controller']);
        $sales = Role::firstOrCreate(['name' => 'Travel Consultant']);
        $purchaser = Role::firstOrCreate(['name' => 'Package Coordinator']);
        $viewer = Role::firstOrCreate(['name' => 'Viewer']);

        // Get permissions by categories
        $bookingPermissions = Permission::where('name', 'like', '%invoice%')->pluck('name'); // Bookings (invoices)
        $travelerPermissions = Permission::where('name', 'like', '%customer%')->pluck('name'); // Travelers (customers)
        $supplierPermissions = Permission::where('name', 'like', '%vendor%')->pluck('name'); // Suppliers (vendors)
        $packagePermissions = Permission::where('name', 'like', '%purchase%')->pluck('name'); // Packages (purchase orders)
        $servicePermissions = Permission::where('name', 'like', '%service%')->pluck('name');
        $categoryPermissions = Permission::where('name', 'like', '%category%')->pluck('name');
        $userPermissions = Permission::where('name', 'like', '%user%')->pluck('name');
        $pagePermissions = Permission::where('name', 'like', 'page_%')->pluck('name');
        $widgetPermissions = Permission::where('name', 'like', 'widget_%')->pluck('name');

        // Travel Manager - Full access to everything
        $manager->syncPermissions(Permission::all());

        // Financial Controller - Full access to bookings, travelers, and financial data
        $accountant->syncPermissions([
            ...$bookingPermissions,
            ...$travelerPermissions,
            ...$pagePermissions,
            ...$widgetPermissions,
            // View-only access to other modules
            'view_vendor',
            'view_any_vendor',
            'view_purchase_order',
            'view_any_purchase_order',
            'view_service',
            'view_any_service',
            'view_category',
            'view_any_category',
            'view_user',
            'view_any_user', // Can view users but not manage them
        ]);

        // Travel Consultant - Traveler and booking management
        $sales->syncPermissions([
            ...$bookingPermissions,
            ...$travelerPermissions,
            ...$servicePermissions,
            ...$categoryPermissions,
            'page_InvoiceSettingsPage',
            'widget_FinancialOverview',
            // View-only access to suppliers and tour packages
            'view_vendor',
            'view_any_vendor',
            'view_purchase_order',
            'view_any_purchase_order',
        ]);

        // Package Coordinator - Supplier and tour package management
        $purchaser->syncPermissions([
            ...$supplierPermissions,
            ...$packagePermissions,
            ...$servicePermissions,
            ...$categoryPermissions,
            'widget_FinancialOverview',
            // View-only access to bookings and travelers
            'view_invoice',
            'view_any_invoice',
            'view_customer',
            'view_any_customer',
        ]);

        // Viewer - Read-only access to everything
        $viewOnlyPermissions = Permission::where('name', 'like', 'view_%')
            ->orWhere('name', 'like', 'widget_%')
            ->orWhere('name', 'like', 'page_%')
            ->pluck('name');

        $viewer->syncPermissions($viewOnlyPermissions);

        echo "✅ Travel agency roles and permissions configured successfully!\n";
        echo "🌍 Created roles: Travel Manager, Financial Controller, Travel Consultant, Package Coordinator, Viewer\n";
    }
}
