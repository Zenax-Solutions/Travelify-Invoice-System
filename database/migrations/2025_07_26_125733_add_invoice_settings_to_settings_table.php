<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert default invoice settings
        $defaultSettings = [
            // Company Information
            'invoice_company_name' => 'TRAVELIFY',
            'invoice_company_tagline' => 'Travel Agency',
            'invoice_logo_enabled' => '1',

            // Contact Information
            'invoice_show_contact_info' => '1',
            'invoice_contact_numbers' => json_encode([
                '(+94) 11 2 502 703',
                '(+94) 770 61 81 73',
                '(+94) 717 61 81 73'
            ]),

            // Invoice Sections Visibility
            'invoice_show_logo_section' => '1',
            'invoice_show_customer_section' => '1',
            'invoice_show_contact_section' => '1',
            'invoice_show_invoice_details_section' => '1',
            'invoice_show_services_table' => '1',
            'invoice_show_payment_info' => '1',
            'invoice_show_terms_section' => '1',
            'invoice_show_footer_section' => '1',

            // Payment Information
            'invoice_payment_accounts' => json_encode([
                [
                    'name' => 'Travelify',
                    'bank' => 'Bank of Ceylon',
                    'account_number' => '93726343',
                    'branch' => 'Visakha Branch - Bambalapitiya',
                    'branch_code' => '775'
                ],
                [
                    'name' => 'Travelify',
                    'bank' => 'Nation Trust Bank',
                    'account_number' => '200250115619 - NATIONS SAVER',
                    'branch' => 'Havelock City',
                    'branch_code' => '025'
                ]
            ]),

            // Footer and Notes
            'invoice_footer_note' => 'THIS IS A COMPUTER-GENERATED TAX INVOICE AND BEARS NO SIGNATURE',
            'invoice_thank_you_message' => 'Thank you for your trust and co-operation',
            'invoice_additional_info' => '',

            // PDF Settings
            'invoice_pdf_footer' => 'TRAVELIFY - Your trusted travel partner',
            'invoice_developer_credit' => 'Developed by ZENAX | www.zenax.info',
            'invoice_show_developer_credit' => '1',

            // Email Settings
            'invoice_email_enabled' => '1',
            'invoice_email_subject' => 'Invoice #{invoice_number} from Travelify',
            'invoice_email_greeting' => 'Dear {customer_name},',
            'invoice_email_message' => 'Thank you for choosing Travelify for your travel needs. Please find your invoice details below:',
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove invoice settings
        Setting::where('key', 'LIKE', 'invoice_%')->delete();
    }
};
