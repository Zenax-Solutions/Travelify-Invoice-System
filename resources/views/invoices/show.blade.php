<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Invoice</title>

    @php
    // Get theme colors with defaults
    $primaryColor = $settings['invoice_primary_color'] ?? '#FF6B35';
    $secondaryColor = $settings['invoice_secondary_color'] ?? '#FF8C00';
    $textColor = $settings['invoice_text_color'] ?? '#333333';
    $backgroundColor = $settings['invoice_background_color'] ?? '#FFFFFF';
    $borderColor = $settings['invoice_border_color'] ?? '#DDDDDD';
    $headerBgColor = $settings['invoice_header_bg_color'] ?? '#FF6B35';
    @endphp

    <style>
        @media print {
            .print-buttons {
                display: none;
            }
        }
    </style>
</head>


@php
// Example: Set this dynamically in your controller or based on invoice data
$status = strtolower($invoice->status); // 'pending', 'paid', 'partially_paid'
@endphp

<body style="font-family: 'Segoe UI', sans-serif; background: {{ $backgroundColor }}; color: {{ $textColor }}; margin: 0; padding: 20px;">
    <div class="invoice-container" style="max-width: 100%; margin: auto; padding: 20px;">
        <div class="print-buttons" style="text-align: right; margin-bottom: 20px;">
            @if(Auth::user())
            <button onclick="window.close()" style="padding: 8px 14px; background-color: #1976d2; color: white; border: none; border-radius: 4px; margin-left: 10px; cursor: pointer;">❌ Close</button>
            <button onclick="window.print()" style="padding: 8px 14px; background-color: #1976d2; color: white; border: none; border-radius: 4px; margin-left: 10px; cursor: pointer;">🖨️ Print</button>
            @endif
            <button onclick="downloadPDF()" style="padding: 8px 14px; background-color: #1976d2; color: white; border: none; border-radius: 4px; margin-left: 10px; cursor: pointer;">⬇️ Download</button>
        </div>

        <div class="info-section" style="display: flex; gap: 20px; justify-content: space-between; font-size: 14px; margin-bottom: 10px;">
            @if(($settings['invoice_show_logo_section'] ?? true) == '1')
            <div class="info-box" style="width: 48%; font-size: 14px; max-width: 20%;">
                @if(($settings['invoice_logo_enabled'] ?? true) == '1')
                <img src="{{asset('logo/logo.png')}}" width="100px" alt="{{ $settings['invoice_company_name'] ?? 'Company' }} Logo">
                @else
                <div style="width: 100px; height: 50px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                    {{ strtoupper($settings['invoice_company_name'] ?? 'LOGO') }}
                </div>
                @endif
            </div>
            @endif

            @if(($settings['invoice_show_customer_section'] ?? true) == '1')
            <div class="info-box" style="width: 48%; font-size: 14px;">
                <strong>Invoice To:</strong><br><br>
                {{ $invoice?->customer->name }}<br>
                {{ $invoice?->customer->address }}<br>
                {{ $invoice?->customer->email }}<br>
                {{ $invoice?->customer->phone }}
            </div>
            @endif

            @if(($settings['invoice_show_contact_section'] ?? true) == '1' && ($settings['invoice_show_contact_info'] ?? true) == '1')
            <div class="info-box" style="width: 48%; font-size: 14px;">
                <strong>Tel:</strong><br><br>
                @if(!empty($settings['invoice_contact_numbers']))
                @foreach($settings['invoice_contact_numbers'] as $number)
                {{ $number }}<br>
                @endforeach
                @else
                <div style="color: #666; font-style: italic; font-size: 12px;">
                    No contact numbers configured.<br>
                    Please set up contact information in settings.
                </div>
                @endif
            </div>
            @endif

            @if(($settings['invoice_show_invoice_details_section'] ?? true) == '1')
            <div class="info-box" style="width: 48%; font-size: 14px; text-align:right">
                <!-- <div class="invoice-title">
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
                        <span class="payment-status {{ $statusClass }}">{{ strtoupper(str_replace('_', ' ', $status == 'paid' ? $status : $status.' Payment ' )) }}</span>
                    </div>
                </div><br> -->
                <strong>Invoice No</strong><br> <span style="color: red;font-weight: bold;">#{{$invoice?->invoice_number}}</span><br>
                <strong>Date:</strong> {{$invoice?->invoice_date->format('Y-m-d')}}<br>
                <strong>Tour Date:</strong> {{$invoice->tour_date->format('Y-m-d')}}<br>
            </div>
            @endif
        </div>

        @if(($settings['invoice_show_services_table'] ?? true) == '1')
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <thead>
                <tr>
                    <th style="width:5%; border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top; background-color: {{ $headerBgColor }}; color: white; text-align: left;">#</th>
                    <th style="width:40%; border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top; background-color: {{ $headerBgColor }}; color: white; text-align: left;">Description</th>
                    <th style="width:5%; border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top; background-color: {{ $headerBgColor }}; color: white; text-align: left;">Qty</th>
                    <th style="width:10%; border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top; background-color: {{ $headerBgColor }}; color: white; text-align: left;">Amount</th>
                    <th style="width:10%; border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top; background-color: {{ $headerBgColor }}; color: white; text-align: left;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice?->services as $key => $item)
                <tr>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top;">{{ $key+1 }}</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top;"> <span style="font-weight: bold;">{{ $item?->name }}</span> <br>
                        <small>{{ $item?->description }}</small>
                    </td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top;">{{ $item->pivot->quantity }}</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top;">{{ number_format($item?->pivot->unit_price, 2) }}</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top;">{{ number_format($item?->pivot->quantity * $item?->pivot->unit_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top; text-align: right; font-weight: bold;">SUB TOTAL</td>
                    <td style="border: 1px solid {{ $borderColor }}; padding: 8px; vertical-align: top; font-weight: bold; color:red">{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif

        @if(($settings['invoice_show_payment_info'] ?? true) == '1')
        <div class="payment-info" style="margin-top: 10px; font-size: 14px;">
            <strong style="font-size: 20px" ;>Payment Info:</strong><br>

            <div style="display: flex;gap: 40px;margin-top: 10px; font-size: 13px;">
                @if(!empty($settings['invoice_payment_accounts']))
                @foreach($settings['invoice_payment_accounts'] as $account)
                <div>
                    <strong>Account Name:</strong> {{ $account['name'] ?? 'Travelify' }}<br>
                    <strong>Bank:</strong> {{ $account['bank'] ?? 'Bank Name' }}<br>
                    <strong>Account Number:</strong> {{ $account['account_number'] ?? 'Account Number' }}<br>
                    <strong>Branch:</strong> {{ $account['branch'] ?? 'Not Set' }}<br>
                    <strong>Branch Code:</strong> {{ $account['branch_code'] ?? 'Not Set' }}<br>
                </div>
                @endforeach
                @else
                <div style="border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9; text-align: center; border-radius: 4px;">
                    <p style="margin: 0; color: #666; font-style: italic;">
                        ⚠️ No payment accounts configured.<br>
                        Please contact administrator to set up payment information.
                    </p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if(($settings['invoice_show_terms_section'] ?? true) == '1')
    <div class="terms" style="font-size: 12px; background: {{ $backgroundColor }}; border: 1px solid {{ $borderColor }}; color: {{ $textColor }}; padding: 10px; margin-top: 20px; border-radius: 4px; text-align: center;">
        <strong><span style="color: red;">NOTE :</span> {{ $settings['invoice_footer_note'] ?? 'THIS IS A COMPUTER-GENERATED TAX INVOICE AND BEARS NO SIGNATURE' }} </strong>
    </div>
    @endif

    @if(($settings['invoice_show_footer_section'] ?? true) == '1')
    <div style="text-align: center; font-style: italic;">
        <h3>{{ $settings['invoice_thank_you_message'] ?? 'Thank you for your trust and co-operation' }}</h3>

        @if($settings['invoice_additional_info'] ?? false)
        <p style="font-size: 12px; color: #666;">{{ $settings['invoice_additional_info'] }}</p>
        @endif

        @if(($settings['invoice_show_developer_credit'] ?? true) == '1' && ($settings['invoice_developer_credit'] ?? false))
        <p style="font-size: 10px; color: #999;">{{ $settings['invoice_developer_credit'] }}</p>
        @endif
    </div>
    @endif
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.invoice-container');
            html2pdf().from(element).save('invoice.pdf');
        }
    </script>
</body>

</html>