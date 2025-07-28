<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // System settings
            ['key' => 'company_name', 'value' => 'Travelify Tours'],
            ['key' => 'company_email', 'value' => 'info@travelify.com'],
            ['key' => 'company_phone', 'value' => '+94 77 123 4567'],
            ['key' => 'company_address', 'value' => 'Colombo, Sri Lanka'],
            ['key' => 'currency', 'value' => 'LKR'],
            // Invoice settings
            ['key' => 'invoice_prefix', 'value' => 'INV-'],
            ['key' => 'invoice_terms', 'value' => 'Payment due within 30 days.'],
            ['key' => 'invoice_footer', 'value' => 'Thank you for your business!'],
            ['key' => 'invoice_tax_rate', 'value' => '0'], // Default tax rate (0 = no tax)
            ['key' => 'invoice_logo', 'value' => 'logo.png'], // Path to logo file in public/logo/
            ['key' => 'invoice_currency_symbol', 'value' => 'Rs.'],
            ['key' => 'invoice_contact_email', 'value' => 'billing@travelify.com'],
            ['key' => 'invoice_contact_phone', 'value' => '+94 77 123 4567'],
        ];
        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }
    }
}
