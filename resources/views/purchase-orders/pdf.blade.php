<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Purchase Order</title>

    @php
    // Define theme color variables
    $primaryColor = $settings['invoice_primary_color'] ?? '#FF6B35';
    $secondaryColor = $settings['invoice_secondary_color'] ?? '#FF8C00';
    $textColor = $settings['invoice_text_color'] ?? '#333333';
    $backgroundColor = $settings['invoice_background_color'] ?? '#FFFFFF';
    $borderColor = $settings['invoice_border_color'] ?? '#DDDDDD';
    $headerBgColor = $settings['invoice_header_bg_color'] ?? '#FF6B35';

    // Status for purchase orders
    $status = strtolower($purchaseOrder->status); // 'pending', 'completed', 'cancelled'
    @endphp

    <style>
        @page {
            margin: 10mm 8mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #FFFFFF;
            color: #333333;
            font-size: 12px;
            line-height: 1.4;
        }

        .container {
            padding: 20px;
        }

        .header {
            width: 100%;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .info-box {
            box-sizing: border-box;
        }

        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #FF6B35;
            margin-bottom: 5px;
        }

        .company-tagline {
            font-size: 14px;
            color: #FF8C00;
            margin-bottom: 10px;
        }

        .po-title {
            text-align: right;
            font-size: 28px;
            font-weight: bold;
            color: #FF6B35;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 8px 15px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
            color: white;
            background-color: #FF6B35;
            border-radius: 4px;
            display: inline-block;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .details-table th,
        .details-table td {
            border: 1px solid #DDDDDD;
            padding: 8px;
            text-align: left;
        }

        .details-table th {
            background-color: #FF6B35;
            color: white;
            font-weight: bold;
        }

        .details-table .text-right {
            text-align: right;
        }

        .summary-table {
            width: 40%;
            float: right;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #DDDDDD;
            padding: 8px;
            text-align: right;
        }

        .summary-table th {
            background-color: #FF6B35;
            color: white;
        }

        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #DDDDDD;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .payment-info {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #FF6B35;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header clearfix">
            <!-- Company Info -->
            @if(($settings['invoice_company_name'] ?? '') || ($settings['invoice_company_tagline'] ?? ''))
            <div class="company-info">
                @if($settings['invoice_company_name'] ?? '')
                <div class="company-name">{{ $settings['invoice_company_name'] }}</div>
                @endif
                @if($settings['invoice_company_tagline'] ?? '')
                <div class="company-tagline">{{ $settings['invoice_company_tagline'] }}</div>
                @endif
            </div>
            @endif

            <!-- Purchase Order Info -->
            <div style="float: left; width: 50%;">
                <div class="info-box">
                    <strong>Vendor:</strong><br>
                    {{ $purchaseOrder->vendor->name }}<br>
                    @if($purchaseOrder->vendor->email)
                    {{ $purchaseOrder->vendor->email }}<br>
                    @endif
                    @if($purchaseOrder->vendor->phone)
                    {{ $purchaseOrder->vendor->phone }}<br>
                    @endif
                    @if($purchaseOrder->vendor->address)
                    {{ $purchaseOrder->vendor->address }}<br>
                    @endif
                </div>
            </div>

            <div style="float: right; width: 50%; text-align: right;">
                <div class="po-title">PURCHASE ORDER</div>
                <div style="margin-bottom: 10px;">
                    <span class="status-badge">{{ strtoupper($status) }}</span>
                </div>
                <strong>PO Number:</strong> <span style="color: red; font-weight: bold;">#{{ $purchaseOrder->po_number }}</span><br>
                <strong>Date:</strong> {{ $purchaseOrder->po_date ? $purchaseOrder->po_date->format('Y-m-d') : 'N/A' }}<br>
                @if(isset($purchaseOrder->expected_delivery_date) && $purchaseOrder->expected_delivery_date)
                <strong>Expected Delivery:</strong> {{ $purchaseOrder->expected_delivery_date->format('Y-m-d') }}<br>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 10%;">#</th>
                    <th style="width: 40%;">Service/Item</th>
                    <th style="width: 15%;">Quantity</th>
                    <th style="width: 15%;">Unit Price</th>
                    <th style="width: 20%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->service->name ?? 'N/A' }}
                        @if($item->description)
                        <br><small style="color: #666;">{{ $item->description }}</small>
                        @endif
                    </td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>Rs{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">Rs{{ number_format($item->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div class="clearfix">
            <table class="summary-table">
                <tr class="total-row">
                    <th>Total Amount:</th>
                    <td>Rs{{ number_format($purchaseOrder->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Paid Amount:</th>
                    <td>Rs{{ number_format($purchaseOrder->total_paid, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <th>Balance:</th>
                    <td>Rs{{ number_format($purchaseOrder->remaining_balance, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Payment Information -->
        @if($purchaseOrder->payments->count() > 0)
        <div class="payment-info">
            <h4 style="margin-top: 0; color: #FF6B35;">Payment History</h4>
            @foreach($purchaseOrder->payments as $payment)
            <div style="margin-bottom: 5px;">
                <strong>{{ $payment->payment_date ? $payment->payment_date->format('Y-m-d') : 'N/A' }}:</strong>
                Rs{{ number_format($payment->amount, 2) }}
                @if($payment->payment_method)
                ({{ $payment->payment_method }})
                @endif
                @if($payment->notes)
                - {{ $payment->notes }}
                @endif
            </div>
            @endforeach
        </div>
        @endif

        <!-- Notes -->
        @if($purchaseOrder->notes)
        <div style="margin-top: 30px;">
            <h4 style="color: #FF6B35;">Notes:</h4>
            <p>{{ $purchaseOrder->notes }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if(($settings['invoice_show_contact_info'] ?? false) && !empty($settings['invoice_contact_numbers'] ?? ''))
            <p><strong>Contact:</strong>
                @if(is_array($settings['invoice_contact_numbers']))
                {{ implode(', ', $settings['invoice_contact_numbers']) }}
                @else
                {{ $settings['invoice_contact_numbers'] }}
                @endif
            </p>
            @endif
            @if(!empty($settings['invoice_footer_note'] ?? ''))
            <p>{{ $settings['invoice_footer_note'] }}</p>
            @endif
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>

</html>