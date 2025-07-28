<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>

    @php
    // Define theme color variables
    $primaryColor = $settings['theme_primary_color'] ?? '#FF6B35';
    $secondaryColor = $settings['theme_secondary_color'] ?? '#FF8C00';
    $textColor = $settings['theme_text_color'] ?? '#333333';
    $backgroundColor = $settings['theme_background_color'] ?? '#FFFFFF';
    $borderColor = $settings['theme_border_color'] ?? '#DDDDDD';
    $headerBgColor = $settings['theme_header_bg_color'] ?? '#FF6B35';
    @endphp

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;

            color: {
                    {
                    $textColor
                }
            }

            ;
        }

        .email-container {
            max-width: 800px;
            margin: 0 auto;

            background-color: {
                    {
                    $backgroundColor
                }
            }

            ;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, {
                    {
                    $primaryColor
                }
            }

            0%, {
                {
                $secondaryColor
            }
        }

        100%);
        color: white;
        padding: 30px;
        text-align: center;
        }

        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .email-content {
            padding: 20px;
        }

        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Use same styling as original invoice */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 20px;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #FF6B35;
            align-items: stretch;
        }

        .info-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            font-size: 14px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .logo-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .logo-box img {
            max-width: 120px;
            width: 100%;
            height: auto;
            margin-bottom: 10px;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin: 20px 0;
        }

        .services-table th,
        .services-table td {
            border: 1px solid {
                    {
                    $borderColor
                }
            }

            ;
            padding: 8px;
            vertical-align: top;
        }

        .services-table th {
            background-color: {
                    {
                    $headerBgColor
                }
            }

            ;
            color: white;
            text-align: left;
        }

        .services-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .total-row {
            font-weight: bold;
        }

        .total-row td:last-child {
            color: red;
            font-weight: bold;
        }

        .payment-info {
            margin-top: 20px;
            font-size: 14px;
        }

        .payment-accounts {
            display: flex;
            gap: 40px;
            margin-top: 10px;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .payment-accounts>div {
            flex: 1;
            min-width: 180px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid orange;
        }

        .terms {
            font-size: 12px;
            background: rgb(255, 239, 210);
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }

        .cta-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #ff8c00 0%, #ff6600 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s ease;
            margin: 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .email-footer {
            background-color: #2c3e50;
            color: white;
            padding: 20px 30px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .info-section {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 15px;
            }

            .info-box {
                min-height: auto;
                padding: 12px;
            }

            .logo-box img {
                max-width: 100px;
            }

            .payment-accounts {
                flex-direction: column;
            }

            .services-table {
                font-size: 12px;
            }

            .services-table th,
            .services-table td {
                padding: 6px 4px;
            }
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
            }

            .email-header {
                padding: 20px;
            }

            .email-header h1 {
                font-size: 24px;
            }

            .email-content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Email Header -->
        <div class="email-header">
            <h1>📄 Invoice from {{ $settings['invoice_company_name'] ?? 'Travelify' }}</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Your {{ strtolower($settings['invoice_company_tagline'] ?? 'travel') }} invoice is ready</p>
        </div>

        <!-- Email Content -->
        <div class="email-content">
            <div class="greeting">
                @php
                $greeting = $settings['invoice_email_greeting'] ?? 'Dear {customer_name},';
                $greeting = str_replace('{customer_name}', $invoice->customer->name, $greeting);
                @endphp
                {!! $greeting !!}
            </div>

            <p>{{ $settings['invoice_email_message'] ?? 'Thank you for choosing Travelify for your travel needs. Please find your invoice details below:' }}</p>

            <!-- Invoice Header - Match original layout with proper alignment -->
            @if(($settings['invoice_show_logo_section'] ?? true) == '1' || ($settings['invoice_show_customer_section'] ?? true) == '1' || ($settings['invoice_show_contact_section'] ?? true) == '1' || ($settings['invoice_show_invoice_details_section'] ?? true) == '1')
            <div class="info-section">
                @if(($settings['invoice_show_logo_section'] ?? true) == '1')
                <div class="info-box logo-box">
                    @if(($settings['invoice_logo_enabled'] ?? true) == '1')
                    <img src="{{asset('logo/logo.png')}}" alt="{{ $settings['invoice_company_name'] ?? 'Company' }} Logo">
                    @endif
                    <strong style="color: #FF6B35; font-size: 16px;">{{ strtoupper($settings['invoice_company_name'] ?? 'COMPANY') }}</strong>
                    <small style="color: #666;">{{ $settings['invoice_company_tagline'] ?? 'Travel Agency' }}</small>
                </div>
                @endif

                @if(($settings['invoice_show_customer_section'] ?? true) == '1')
                <div class="info-box">
                    <strong style="color: #2c3e50; border-bottom: 2px solid #FF6B35; padding-bottom: 5px; margin-bottom: 10px; display: inline-block;">Invoice To:</strong><br>
                    <div style="line-height: 1.6;">
                        <strong>{{ $invoice->customer->name }}</strong><br>
                        {{ $invoice->customer->address }}<br>
                        <span style="color: #FF6B35;">📧</span> {{ $invoice->customer->email }}<br>
                        <span style="color: #FF6B35;">📱</span> {{ $invoice->customer->phone }}
                    </div>
                </div>
                @endif

                @if(($settings['invoice_show_contact_section'] ?? true) == '1' && ($settings['invoice_show_contact_info'] ?? true) == '1')
                <div class="info-box">
                    <strong style="color: #2c3e50; border-bottom: 2px solid #FF6B35; padding-bottom: 5px; margin-bottom: 10px; display: inline-block;">Contact Info:</strong><br>
                    <div style="line-height: 1.6;">
                        @if(!empty($settings['invoice_contact_numbers']))
                        @foreach($settings['invoice_contact_numbers'] as $number)
                        <span style="color: #FF6B35;">☎️</span> {{ $number }}<br>
                        @endforeach
                        @else
                        <div style="color: #666; font-style: italic; font-size: 12px;">
                            <span style="color: #FF6B35;">⚠️</span> No contact numbers configured
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                @if(($settings['invoice_show_invoice_details_section'] ?? true) == '1')
                <div class="info-box" style="text-align: right;">
                    <strong style="color: #2c3e50; border-bottom: 2px solid #FF6B35; padding-bottom: 5px; margin-bottom: 10px; display: inline-block;">Invoice Details:</strong><br>
                    <div style="line-height: 1.8;">
                        <strong style="color: #FF6B35; font-size: 18px;">#{{ $invoice->invoice_number }}</strong><br>
                        <strong>Date:</strong> {{ $invoice->invoice_date->format('Y-m-d') }}<br>
                        @if($invoice->due_date)
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('Y-m-d') }}<br>
                        @endif
                        @if($invoice->tour_date)
                        <strong style="color: #FF6B35;">🗓️ Tour Date:</strong> {{ $invoice->tour_date->format('Y-m-d') }}<br>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Services Table - Match original styling -->
            @if(($settings['invoice_show_services_table'] ?? true) == '1')
            <table class="services-table">
                <thead>
                    <tr>
                        <th style="width: 5%">#</th>
                        <th style="width: 40%">Description</th>
                        <th style="width: 5%">Qty</th>
                        <th style="width: 10%">Amount</th>
                        <th style="width: 10%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->services as $key => $service)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>
                            <span style="font-weight: bold;">{{ $service->name }}</span>
                            @if($service->description)
                            <br><small>{{ $service->description }}</small>
                            @endif
                        </td>
                        <td>{{ $service->pivot->quantity }}</td>
                        <td>{{ number_format($service->pivot->unit_price, 2) }}</td>
                        <td>{{ number_format($service->pivot->quantity * $service->pivot->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;">
                            <strong>SUB TOTAL</strong>
                        </td>
                        <td>
                            <strong>{{ number_format($invoice->total_amount, 2) }}</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
            @endif

            <!-- Payment Information - Match original styling -->
            @if(($settings['invoice_show_payment_info'] ?? true) == '1')
            <div class="payment-info">
                <strong style="font-size: 20px;">Payment Info:</strong><br>

                <div class="payment-accounts">
                    @if(!empty($settings['invoice_payment_accounts']))
                    @foreach($settings['invoice_payment_accounts'] as $account)
                    <div>
                        <strong>Account Name:</strong> {{ $account['name'] ?? 'Travelify' }}<br>
                        <strong>Bank:</strong> {{ $account['bank'] ?? 'Bank Name' }}<br>
                        <strong>Account Number:</strong> {{ $account['account_number'] ?? 'Account Number' }}<br>
                        <strong>Branch:</strong> {{ $account['branch'] ?? 'Branch Name' }}<br>
                        <strong>Branch Code:</strong> {{ $account['branch_code'] ?? 'Not Set' }}<br>
                    </div>
                    @endforeach
                    @else
                    <div style="border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9; text-align: center; border-radius: 4px; margin-top: 10px;">
                        <p style="margin: 0; color: #666; font-style: italic;">
                            ⚠️ No payment accounts configured.<br>
                            Please contact administrator to set up payment information.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Call to Action -->
            <div class="cta-section">
                <a href="{{ route('invoices.show', $invoice) }}" class="btn" style="margin-right: 10px;">
                    🔍 View Full Invoice Online
                </a>
                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn" style="background: #28a745;">
                    📄 Download PDF
                </a>
            </div>

            <!-- Terms - Match original styling -->
            @if(($settings['invoice_show_terms_section'] ?? true) == '1')
            <div class="terms">
                <strong><span style="color: red;">NOTE :</span> {{ $settings['invoice_footer_note'] ?? 'THIS IS A COMPUTER-GENERATED TAX INVOICE AND BEARS NO SIGNATURE' }} </strong>
            </div>
            @endif

            @if(($settings['invoice_show_footer_section'] ?? true) == '1')
            <p style="color: #7f8c8d; font-style: italic; text-align: center;">
                <strong>{{ $settings['invoice_thank_you_message'] ?? 'Thank you for your trust and co-operation' }}</strong> 🙏
            </p>

            @if($settings['invoice_additional_info'] ?? false)
            <p style="color: #666; font-size: 12px; text-align: center;">{{ $settings['invoice_additional_info'] }}</p>
            @endif
            @endif
        </div>

        <!-- Email Footer -->
        <div class="email-footer">
            <p style="margin: 0;"><strong>{{ strtoupper($settings['invoice_company_name'] ?? 'COMPANY') }}</strong></p>
            <p style="margin: 5px 0 10px 0; opacity: 0.8;">{{ $settings['invoice_pdf_footer'] ?? 'Your trusted travel partner' }}</p>
            <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.2); margin: 15px 0;">
            @if(($settings['invoice_show_developer_credit'] ?? true) == '1' && ($settings['invoice_developer_credit'] ?? false))
            <p style="margin: 0; font-size: 12px; opacity: 0.7;">
                {{ $settings['invoice_developer_credit'] }}
            </p>
            @endif
            Developed by <strong>ZENAX</strong> |
            <a href="https://www.zenax.info" style="color: #FF6B35; text-decoration: none;">www.zenax.info</a>
            </p>
        </div>
    </div>
</body>

</html>