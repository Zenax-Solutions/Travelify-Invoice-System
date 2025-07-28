<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function show(PurchaseOrder $purchaseOrder)
    {
        $settings = Setting::getMany([
            'invoice_company_name',
            'invoice_company_tagline',
            'invoice_logo_enabled',
            'invoice_show_contact_info',
            'invoice_contact_numbers',
            'invoice_show_logo_section',
            'invoice_show_customer_section',
            'invoice_show_contact_section',
            'invoice_show_invoice_details_section',
            'invoice_show_services_table',
            'invoice_show_payment_info',
            'invoice_show_terms_section',
            'invoice_show_footer_section',
            'invoice_payment_accounts',
            'invoice_footer_note',
            'invoice_thank_you_message',
            'invoice_additional_info',
            'invoice_primary_color',
            'invoice_secondary_color',
            'invoice_text_color',
            'invoice_background_color',
            'invoice_border_color',
            'invoice_header_bg_color',
        ]);

        return view('purchase-orders.show', compact('purchaseOrder', 'settings'));
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        $settings = Setting::getMany([
            'invoice_company_name',
            'invoice_company_tagline',
            'invoice_logo_enabled',
            'invoice_show_contact_info',
            'invoice_contact_numbers',
            'invoice_show_logo_section',
            'invoice_show_customer_section',
            'invoice_show_contact_section',
            'invoice_show_invoice_details_section',
            'invoice_show_services_table',
            'invoice_show_payment_info',
            'invoice_show_terms_section',
            'invoice_show_footer_section',
            'invoice_payment_accounts',
            'invoice_footer_note',
            'invoice_thank_you_message',
            'invoice_additional_info',
            'invoice_primary_color',
            'invoice_secondary_color',
            'invoice_text_color',
            'invoice_background_color',
            'invoice_border_color',
            'invoice_header_bg_color',
        ]);

        return view('purchase-orders.pdf', compact('purchaseOrder', 'settings'));
    }

    public function downloadPDF(PurchaseOrder $purchaseOrder)
    {
        $settings = Setting::getMany([
            'invoice_company_name',
            'invoice_company_tagline',
            'invoice_logo_enabled',
            'invoice_show_contact_info',
            'invoice_contact_numbers',
            'invoice_show_logo_section',
            'invoice_show_customer_section',
            'invoice_show_contact_section',
            'invoice_show_invoice_details_section',
            'invoice_show_services_table',
            'invoice_show_payment_info',
            'invoice_show_terms_section',
            'invoice_show_footer_section',
            'invoice_payment_accounts',
            'invoice_footer_note',
            'invoice_thank_you_message',
            'invoice_additional_info',
            'invoice_primary_color',
            'invoice_secondary_color',
            'invoice_text_color',
            'invoice_background_color',
            'invoice_border_color',
            'invoice_header_bg_color',
        ]);

        $pdf = Pdf::loadView('purchase-orders.pdf', compact('purchaseOrder', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('purchase-order-' . $purchaseOrder->po_number . '.pdf');
    }
}
