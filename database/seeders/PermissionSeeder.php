<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Invoice permissions
        $this->createPermissions([
            'view_any_invoice',
            'view_invoice',
            'create_invoice',
            'update_invoice',
            'delete_invoice',
            'force_delete_invoice',
            'restore_invoice'
        ]);

        // Customer permissions
        $this->createPermissions([
            'view_any_customer',
            'view_customer',
            'create_customer',
            'update_customer',
            'delete_customer'
        ]);

        // Vendor permissions
        $this->createPermissions([
            'view_any_vendor',
            'view_vendor',
            'create_vendor',
            'update_vendor',
            'delete_vendor'
        ]);

        // Purchase order permissions
        $this->createPermissions([
            'view_any_purchase_order',
            'view_purchase_order',
            'create_purchase_order',
            'update_purchase_order',
            'delete_purchase_order'
        ]);

        // Service permissions
        $this->createPermissions([
            'view_any_service',
            'view_service',
            'create_service',
            'update_service',
            'delete_service'
        ]);

        // Category permissions
        $this->createPermissions([
            'view_any_category',
            'view_category',
            'create_category',
            'update_category',
            'delete_category'
        ]);

        // User permissions
        $this->createPermissions([
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user'
        ]);

        // Page permissions
        $this->createPermissions([
            'page_Dashboard',
            'page_Settings',
            'page_Reports',
            'page_InvoiceSettingsPage'
        ]);

        // Widget permissions
        $this->createPermissions([
            'widget_RevenueChart',
            'widget_InvoiceStats',
            'widget_RecentTransactions',
            'widget_FinancialOverview'
        ]);
    }

    private function createPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
