<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice): View|Response
    {
        try {
            // Check if user has permission to view this invoice
            // You can implement more sophisticated authorization logic here

            $invoice->load('customer', 'services', 'items.service', 'payments');

            // Get invoice settings for consistent display
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
                'invoice_pdf_footer',
                'invoice_developer_credit',
                'invoice_show_developer_credit',
                // Theme colors
                'invoice_primary_color',
                'invoice_secondary_color',
                'invoice_text_color',
                'invoice_background_color',
                'invoice_border_color',
                'invoice_header_bg_color',
            ]);

            // Decode JSON fields
            if (isset($settings['invoice_contact_numbers'])) {
                $settings['invoice_contact_numbers'] = json_decode($settings['invoice_contact_numbers'], true) ?: [];
            }

            if (isset($settings['invoice_payment_accounts'])) {
                $settings['invoice_payment_accounts'] = json_decode($settings['invoice_payment_accounts'], true) ?: [];
            }

            return view('invoices.show', compact('invoice', 'settings'));
        } catch (\Exception $e) {
            Log::error('Error displaying invoice: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            return response()->view('errors.500', [], 500);
        }
    }

    public function downloadPDF(Invoice $invoice)
    {
        try {
            // Load necessary relationships
            $invoice->load('customer', 'services', 'items.service', 'payments');

            // Prepare logo data for PDF
            $logoPath = public_path('logo/logo.png');
            $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;

            // Get invoice settings
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
                'invoice_pdf_footer',
                'invoice_developer_credit',
                'invoice_show_developer_credit',
                // Theme colors
                'invoice_primary_color',
                'invoice_secondary_color',
                'invoice_text_color',
                'invoice_background_color',
                'invoice_border_color',
                'invoice_header_bg_color',
            ]);

            // Decode JSON fields
            if (isset($settings['invoice_contact_numbers'])) {
                $settings['invoice_contact_numbers'] = json_decode($settings['invoice_contact_numbers'], true) ?: [];
            }

            if (isset($settings['invoice_payment_accounts'])) {
                $settings['invoice_payment_accounts'] = json_decode($settings['invoice_payment_accounts'], true) ?: [];
            }

            // Generate PDF using the clean PDF view with proper settings
            $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'logoData', 'settings'))
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi' => 120,
                    'defaultFont' => 'sans-serif',
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                ]);

            // Return PDF download response
            return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error generating PDF for invoice: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id(),
            ]);

            return response()->view('errors.500', [], 500);
        }
    }
}
