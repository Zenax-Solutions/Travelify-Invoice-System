<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #FF6B35;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
        }

        .tagline {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 20px 0;
        }

        .po-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if($settings['invoice_company_name'] ?? '')
            <div class="company-name">{{ $settings['invoice_company_name'] }}</div>
            @endif
            @if($settings['invoice_company_tagline'] ?? '')
            <div class="tagline">{{ $settings['invoice_company_tagline'] }}</div>
            @endif
        </div>

        <div class="content">
            <h2>Purchase Order Notification</h2>

            <p>Dear {{ $purchaseOrder->vendor->name }},</p>

            <p>We have generated a purchase order for your services. Please find the details below:</p>

            <div class="po-details">
                <strong>Purchase Order #:</strong> {{ $purchaseOrder->po_number }}<br>
                <strong>Date:</strong> {{ $purchaseOrder->po_date ? $purchaseOrder->po_date->format('M d, Y') : 'N/A' }}<br>
                <strong>Total Amount:</strong> Rs{{ number_format($purchaseOrder->total_amount, 2) }}<br>
                <strong>Status:</strong> {{ ucfirst($purchaseOrder->status) }}<br>
                @if($purchaseOrder->notes)
                <strong>Notes:</strong> {{ $purchaseOrder->notes }}<br>
                @endif
            </div>

            <p>The detailed purchase order is attached to this email as a PDF document.</p>

            <p>If you have any questions regarding this purchase order, please don't hesitate to contact us.</p>

            <p>Thank you for your services!</p>
        </div>

        <div class="footer">
            @if(($settings['invoice_show_contact_info'] ?? false) && !empty($settings['invoice_contact_numbers']))
            <p>Contact:
                @if(is_array($settings['invoice_contact_numbers']))
                {{ implode(', ', $settings['invoice_contact_numbers']) }}
                @else
                {{ $settings['invoice_contact_numbers'] }}
                @endif
            </p>
            @endif
            @if(!empty($settings['invoice_email_footer'] ?? ''))
            <p>{{ $settings['invoice_email_footer'] }}</p>
            @endif
        </div>
    </div>
</body>

</html>