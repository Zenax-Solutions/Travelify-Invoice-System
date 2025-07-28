<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .email-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .email-header {
            background: linear-gradient(135deg, #ff8c00 0%, #ff6600 100%);
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
            display: flex;
            gap: 20px;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .info-box {
            flex: 1;
            min-width: 200px;
            font-size: 14px;
        }

        .services-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin: 20px 0;
        }

        .services-table th,
        .services-table td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: top;
        }

        .services-table th {
            background-color: orange;
            color: black;
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

        @media (max-width: 600px) {
            .info-section {
                flex-direction: column;
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
    </style>
</head>

<body>
    <div class="email-container">
        <!-- Email Header -->
        <div class="email-header">
            <h1>📄 Invoice from Travelify</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Your travel invoice is ready</p>
        </div>

        <!-- Email Content -->
        <div class="email-content">
            <div class="greeting">
                Dear <strong>{{ $invoice->customer->name }}</strong>,
            </div>

            <p>Thank you for choosing Travelify for your travel needs. Please find your invoice details below:</p>

            <!-- Invoice Header - Match original layout -->
            <div class="info-section">
                <div class="info-box" style="max-width: 150px;">
                    <img src="{{asset('logo/logo.png')}}" width="100px" alt="Travelify Logo" style="max-width: 100%; height: auto;">
                </div>
                <div class="info-box">
                    <strong>Invoice To:</strong><br><br>
                    {{ $invoice->customer->name }}<br>
                    {{ $invoice->customer->address }}<br>
                    {{ $invoice->customer->email }}<br>
                    {{ $invoice->customer->phone }}
                </div>
                <div class="info-box">
                    <strong>Tel:</strong><br><br>
                    (+94) 11 2 502 703<br>
                    (+94) 770 61 81 73<br>
                    (+94) 717 61 81 73
                </div>
                <div class="info-box" style="text-align: right;">
                    <strong>Invoice No</strong><br>
                    <span style="color: red;font-weight: bold;">#{{ $invoice->invoice_number }}</span><br>
                    <strong>Date:</strong> {{ $invoice->invoice_date->format('Y-m-d') }}<br><br>
                    @if($invoice->due_date)
                    <strong>Due Date:</strong> {{ $invoice->due_date->format('Y-m-d') }}<br>
                    @endif
                    @if($invoice->tour_date)
                    <strong>Tour Date:</strong> {{ $invoice->tour_date->format('Y-m-d') }}<br>
                    @endif
                </div>
            </div>

            <!-- Services Table - Match original styling -->
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

            <!-- Payment Information - Match original styling -->
            <div class="payment-info">
                <strong style="font-size: 20px;">Payment Info:</strong><br>

                <div class="payment-accounts">
                    <div>
                        <strong>Account Name:</strong> Travelify<br>
                        <strong>Bank:</strong> Bank of Ceylon<br>
                        <strong>Account Number:</strong> 93726343<br>
                        <strong>Branch:</strong> Visakha Branch - Bambalapitiya<br>
                        <strong>Branch Code:</strong> 775<br>
                    </div>

                    <div>
                        <strong>Account Name:</strong> Travelify<br>
                        <strong>Bank:</strong> Nation Trust Bank<br>
                        <strong>Account Number:</strong> 200250115619 - NATIONS SAVER<br>
                        <strong>Branch:</strong> Havelock City<br>
                        <strong>Branch Code:</strong> 025<br>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="cta-section">
                <a href="{{ route('invoices.show', $invoice) }}" class="btn">
                    🔍 View Full Invoice Online
                </a>
            </div>

            <!-- Terms - Match original styling -->
            <div class="terms">
                <strong><span style="color: red;">NOTE :</span> THIS IS A COMPUTER-GENERATED TAX INVOICE AND BEARS NO SIGNATURE </strong>
            </div>

            <p style="color: #7f8c8d; font-style: italic; text-align: center;">
                <strong>Thank you for your trust and co-operation</strong> 🙏
            </p>
        </div>

        <!-- Email Footer -->
        <div class="email-footer">
            <p style="margin: 0;"><strong>{{ config('app.name') }}</strong></p>
            <p style="margin: 5px 0 0 0; opacity: 0.8;">Your trusted travel partner</p>
        </div>
    </div>
</body>

</html>