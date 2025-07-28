<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public PurchaseOrder $purchaseOrder,
    ) {
        // Get email settings (reusing invoice settings for now)
        $this->settings = Setting::getMany([
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
            'invoice_email_enabled',
            'invoice_email_subject',
            'invoice_email_greeting',
            'invoice_email_message',
            'invoice_primary_color',
            'invoice_secondary_color',
            'invoice_text_color',
            'invoice_background_color',
            'invoice_border_color',
            'invoice_header_bg_color',
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Purchase Order #' . $this->purchaseOrder->po_number;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-orders.sent',
            with: [
                'purchaseOrder' => $this->purchaseOrder,
                'settings' => $this->settings,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('purchase-orders.pdf', [
            'purchaseOrder' => $this->purchaseOrder,
            'settings' => $this->settings,
        ])->setPaper('a4', 'portrait');

        return [
            Attachment::fromData(
                fn() => $pdf->output(),
                'purchase-order-' . $this->purchaseOrder->po_number . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
