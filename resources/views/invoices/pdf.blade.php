<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invoice</title>

    @php
    // Define theme color variables
    $primaryColor = $settings['invoice_primary_color'] ?? '#FF6B35';
    $secondaryColor = $settings['invoice_secondary_color'] ?? '#FF8C00';
    $textColor = $settings['invoice_text_color'] ?? '#333333';
    $backgroundColor = $settings['invoice_background_color'] ?? '#FFFFFF';
    $borderColor = $settings['invoice_border_color'] ?? '#DDDDDD';
    $headerBgColor = $settings['invoice_header_bg_color'] ?? '#FF6B35';

    // Example: Set this dynamically in your controller or based on invoice data
    $status = strtolower($invoice->status); // 'pending', 'paid', 'partially_paid'
    @endphp

    <style>
        @page {
            margin: 10mm 8mm;
            size: A4 portrait;
        }

        * {
            box-sizing: border-box;
        }

        .print-buttons {
            display: none;
        }

        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>

<body style="font-family: 'DejaVu Sans', sans-serif; background: {{ $backgroundColor }}; color: {{ $textColor }}; margin: 0; padding: 5px; line-height: 1.3;">
    <div class="invoice-container" style="max-width: 100%; margin: 0; padding: 0; page-break-inside: avoid;">
        <div class="info-section clearfix" style="width: 100%; font-size: 14px; margin-bottom: 8px;">
            @if(($settings['invoice_show_logo_section'] ?? true) == '1')
            <div class="info-box" style="float: left; width: 25%; padding: 8px 3px; font-size: 14px; line-height: 1.4; max-width: 20%;">
                @if(($settings['invoice_logo_enabled'] ?? true) == '1' && $logoData)
                <img src="data:image/png;base64,{{ $logoData }}" width="100px" alt="{{ $settings['invoice_company_name'] ?? 'Company' }} Logo" style="max-width: 100px; height: auto;">
                @else
                <div style="width: 100px; height: 50px; border: 1px solid #ccc; text-align: center; font-size: 12px; font-weight: bold; padding-top: 18px; box-sizing: border-box;">
                    {{ strtoupper($settings['invoice_company_name'] ?? 'LOGO') }}
                </div>
                @endif
            </div>
            @endif
            @if(($settings['invoice_show_customer_section'] ?? true) == '1')
            <div class="info-box" style="float: left; width: 25%; padding: 8px 3px; font-size: 14px; line-height: 1.4;">
                <strong>Invoice To:</strong><br><br>
                {{ $invoice->customer->name }}<br>
                {{ $invoice->customer->address }}<br>
                {{ $invoice->customer->email }}<br>
                {{ $invoice->customer->phone }}
            </div>
            @endif
            @if(($settings['invoice_show_contact_section'] ?? true) == '1' && ($settings['invoice_show_contact_info'] ?? true) == '1')
            <div class="info-box" style="float: left; width: 25%; padding: 8px 3px; font-size: 14px; line-height: 1.4;">
                <strong>Tel:</strong><br><br>
                @if(!empty($settings['invoice_contact_numbers']))
                @foreach($settings['invoice_contact_numbers'] as $number)
                {{ $number }}<br>
                @endforeach
                @else
                <div style="color: #666; font-style: italic; font-size: 10px;">
                    No contact numbers configured
                </div>
                @endif
            </div>
            @endif
            @if(($settings['invoice_show_invoice_details_section'] ?? true) == '1')
            <div class="info-box" style="float: left; width: 25%; padding: 8px 3px; font-size: 14px; line-height: 1.4; text-align:right">
                <div class="invoice-title" style="text-align: right; font-size: 28px; font-weight: bold; color: {{ $primaryColor }};">
                    INVOICE<br>

                    @php
                    $statusClass = match($status) {
                    'pending' => 'status-pending',
                    'paid' => 'status-paid',
                    'partially_paid' => 'status-partially_paid',
                    default => 'status-pending',
                    };
                    @endphp

                    <div>
                        <span class="payment-status" style="padding: 10px; font-weight: bold; font-size: 14px; text-transform: uppercase; color: white; background-color: {{ $statusClass == 'status-pending' ? $secondaryColor : $primaryColor }}; {{ $statusClass == 'status-partially_paid' ? 'opacity: 0.8;' : '' }}">{{ strtoupper(str_replace('_', ' ', $status == 'paid' ? $status : $status.' Payment ' )) }}</span>
                    </div>
                </div><br>
                <strong>Invoice No</strong><br> <span style="color: red;font-weight: bold;">#{{ $invoice->invoice_number }}</span><br>
                <strong>Date:</strong> {{$invoice?->invoice_date->format('Y-m-d')}}<br>
                @if($invoice->due_date)
                <strong>Due Date:</strong> {{$invoice->due_date->format('Y-m-d')}}<br>
                @endif
                @if($invoice->tour_date)
                <strong>Tour Date:</strong> {{$invoice->tour_date->format('Y-m-d')}}<br>
                @endif
                <br>

            </div>
            @endif
        </div>

        <div style="clear: both; margin-bottom: 10px;"></div>

        @if(($settings['invoice_show_services_table'] ?? true) == '1')
        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin: 10px 0; page-break-inside: avoid;">
            <thead>
                <tr>
                    <th style="width:5%; border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word; background-color: {{ $headerBgColor }}; color: white; text-align: left; font-weight: bold;">#</th>
                    <th style="width:40%; border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word; background-color: {{ $headerBgColor }}; color: white; text-align: left; font-weight: bold;">Description</th>
                    <th style="width:5%; border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word; background-color: {{ $headerBgColor }}; color: white; text-align: left; font-weight: bold;">Qty</th>
                    <th style="width:10%; border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word; background-color: {{ $headerBgColor }}; color: white; text-align: left; font-weight: bold;">Amount</th>
                    <th style="width:10%; border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word; background-color: {{ $headerBgColor }}; color: white; text-align: left; font-weight: bold;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->services as $key => $item)
                <tr>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word;">{{ $key+1 }}</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word;"> <span style="font-weight: bold;">{{ $item->name }}</span> <br>
                        <small>{{ $item->description }}</small>
                    </td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word;">{{ $item->pivot->quantity }}</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word;">{{ number_format($item->pivot->unit_price, 2) }}</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word;">{{ number_format($item->pivot->quantity * $item->pivot->unit_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word; text-align: right; font-weight: bold;">SUB TOTAL</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 6px 4px; vertical-align: top; word-wrap: break-word; font-weight: bold; color:red">{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif


        @if(($settings['invoice_show_payment_info'] ?? true) == '1')
        <div style="margin-top: 15px;">
            <div class="payment-info" style="page-break-inside: avoid;">
                <strong style="font-size: 16px; color: {{ $primaryColor }}; margin-bottom: 10px; display: block; border-bottom: 2px solid {{ $primaryColor }}; padding-bottom: 5px;">Payment Information</strong>

                @if(!empty($settings['invoice_payment_accounts']))
                <div style="margin-top: 8px;">
                    @foreach($settings['invoice_payment_accounts'] as $index => $account)
                    <div style="float: left; width: 45%; margin-right: 5%; margin-bottom: 8px; font-size: 12px; line-height: 1.6;">
                        <strong>Account Name:</strong> {{ $account['name'] ?? $settings['invoice_company_name'] ?? 'Not Set' }}<br>
                        <strong>Bank:</strong> {{ $account['bank'] ?? 'Not Set' }}<br>
                        <strong>Account Number:</strong> {{ $account['account_number'] ?? 'Not Set' }}<br>
                        <strong>Branch:</strong> {{ $account['branch'] ?? 'Not Set' }}<br>
                        <strong>Branch Code:</strong> {{ $account['branch_code'] ?? 'Not Set' }}
                    </div>
                    @if(($index + 1) % 2 == 0)
                    <div style="clear: both;"></div>
                    @endif
                    @endforeach
                    <div style="clear: both;"></div>
                </div>
                @else
                <div style="border: 1px solid #ddd; padding: 8px; background-color: #f9f9f9; text-align: center; margin-top: 5px; border-radius: 4px;">
                    <p style="margin: 0; color: #666; font-style: italic; font-size: 12px;">
                        ⚠️ No payment accounts configured.<br>
                        Please contact administrator to set up payment information.
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if(($settings['invoice_show_terms_section'] ?? true) == '1')
        <div style="margin-top: 10px;">
            <div class="terms" style="font-size: 12px; background: {{ $backgroundColor }}; border: 1px solid {{ $borderColor }}; padding: 8px; margin-top: 10px; border-radius: 4px; page-break-inside: avoid; text-align: center;">
                <strong><span style="color: red;">NOTE :</span> {{ $settings['invoice_footer_note'] ?? 'THIS IS A COMPUTER-GENERATED TAX INVOICE AND BEARS NO SIGNATURE' }} </strong>
            </div>
        </div>
        @endif

        @if(($settings['invoice_show_footer_section'] ?? true) == '1')
        <div style="margin-top: 2px;">
            <div class="final-section" style="text-align: center; font-style: italic; margin-top: 5px; page-break-inside: avoid;">
                <h3>{{ $settings['invoice_thank_you_message'] ?? 'Thank you for your trust and co-operation' }}</h3>

                @if($settings['invoice_additional_info'] ?? false)
                <p style="font-size: 12px; color: #666;">{{ $settings['invoice_additional_info'] }}</p>
                @endif

                @if(($settings['invoice_show_developer_credit'] ?? true) == '1' && ($settings['invoice_developer_credit'] ?? false))
                <p style="font-size: 10px; color: #999; text-align: center;">{{ $settings['invoice_developer_credit'] }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</body>

</html>