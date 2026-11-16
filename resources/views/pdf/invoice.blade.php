<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoiceTitle ?? 'Tax Invoice' }} - {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 10mm 12mm 14mm 12mm;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            line-height: 1.35;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table {
            margin-bottom: 14px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }
        .logo-box {
            background-color: #1e40af;
            color: #ffffff;
            width: 34px;
            height: 34px;
            line-height: 34px;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            display: inline-block;
        }
        .brand-title {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .brand-subtitle {
            font-size: 8.5pt;
            font-weight: bold;
            color: #1e40af;
        }
        .corp-details {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 3px;
            line-height: 1.3;
        }
        .invoice-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .invoice-meta {
            text-align: right;
            font-size: 8pt;
            color: #334155;
            margin-top: 3px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 7.5pt;
            font-weight: bold;
            margin-top: 4px;
            border-width: 1px;
            border-style: solid;
        }
        .meta-cards-table {
            margin-bottom: 12px;
        }
        .meta-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 8pt;
            height: 105px;
            vertical-align: top;
        }
        .meta-card-title {
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e40af;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 5px;
            letter-spacing: 0.3px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            page-break-inside: auto;
        }
        .items-table thead {
            display: table-header-group;
        }
        .items-table tr {
            page-break-inside: avoid;
        }
        .items-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 7.5pt;
            text-transform: uppercase;
            padding: 6px 7px;
            border: 1px solid #1e3a8a;
            letter-spacing: 0.3px;
        }
        .items-table td {
            padding: 6px 7px;
            border: 1px solid #cbd5e1;
            font-size: 8pt;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .pkg-header-row td {
            background-color: #eff6ff !important;
            color: #1e40af;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 4px 7px;
            border: 1px solid #bfdbfe;
        }
        .financial-summary-table {
            margin-top: 4px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .tax-analysis-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        .tax-analysis-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            font-size: 7pt;
            text-align: right;
        }
        .tax-analysis-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            text-align: right;
        }
        .totals-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .totals-table td {
            padding: 3px 0;
        }
        .totals-table tr.grand-total td {
            border-top: 1.5px solid #1e3a8a;
            border-bottom: 1.5px solid #1e3a8a;
            font-weight: bold;
            font-size: 9.5pt;
            color: #0f172a;
            padding: 5px 0;
        }
        .words-banner {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 7.5pt;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .footer-box {
            border-top: 1px solid #cbd5e1;
            padding-top: 8px;
            font-size: 7pt;
            color: #64748b;
            line-height: 1.4;
            page-break-inside: avoid;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-muted {
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- Professional Invoice Header -->
    <table class="header-table" width="100%">
        <tr>
            <td width="60%" style="vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 40px; vertical-align: top;">
                            <div class="logo-box">AZ</div>
                        </td>
                        <td style="vertical-align: top; padding-left: 8px;">
                            <div class="brand-title">{{ setting('name', 'ALLZONE') }}</div>
                            <div class="brand-subtitle">Operated by {{ setting('company_name', 'SK Refrigeration') }}</div>
                        </td>
                    </tr>
                </table>
                <div class="corp-details">
                    Corporate Hub: {{ setting('company_address', setting('default_city', 'Bengaluru') . ', Karnataka - ' . setting('default_pincode', '560001')) }}<br>
                    Support Email: {{ setting('support_email', 'support@allzone.in') }} | Phone: {{ setting('support_phone', '1800-ALLZONE') }}<br>
                    @if(!empty(setting('platform_gstin')) || !empty(config('allzone.gstin')))
                        <strong>Platform GSTIN:</strong> {{ setting('platform_gstin') ?: config('allzone.gstin') }}<br>
                    @endif
                    Authorized Multi-Vendor Electronics &amp; Home Appliances Marketplace
                </div>
            </td>
            <td width="40%" style="vertical-align: top;" class="text-right">
                <div class="invoice-title">{{ $invoiceTitle ?? 'TAX INVOICE' }}</div>
                <div class="invoice-meta">
                    <strong>Invoice #:</strong> {{ $invoiceNumber ?? ('INV-' . $order->order_number) }}<br>
                    <strong>Date &amp; Time:</strong> {{ $order->created_at->format('d M Y, h:i A') }}<br>
                    <strong>Place of Supply:</strong> {{ $customerState }} (Code: {{ $placeOfSupplyCode }})
                </div>
                <div style="margin-top: 5px;">
                    <span class="badge" style="background-color: {{ $badgeBg ?? '#dcfce7' }}; color: {{ $badgeColor ?? '#15803d' }}; border-color: {{ $badgeColor ?? '#15803d' }};">
                        {{ $statusBadge ?? 'PAID' }}
                    </span>
                    <span class="badge" style="background-color: #f1f5f9; color: #334155; border-color: #cbd5e1;">
                        {{ strtoupper($order->payment_method->label() ?? 'ONLINE') }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Billing & Shipping / Order Information Cards -->
    <table class="meta-cards-table" width="100%">
        <tr>
            <td width="50%" style="padding-right: 6px; vertical-align: top;">
                <div class="meta-card">
                    <div class="meta-card-title">Billed &amp; Shipped To:</div>
                    <strong>{{ $order->address_snapshot['name'] ?? $order->customer?->user?->name ?? 'Valued Customer' }}</strong><br>
                    {{ $order->address_snapshot['line1'] ?? '' }}
                    @if(!empty($order->address_snapshot['line2']))
                        , {{ $order->address_snapshot['line2'] }}
                    @endif
                    <br>
                    {{ $order->address_snapshot['city'] ?? '' }}, {{ $order->address_snapshot['state'] ?? '' }} - {{ $order->address_snapshot['pincode'] ?? '' }}<br>
                    <strong>Contact Phone:</strong> {{ $order->address_snapshot['phone'] ?? $order->customer?->user?->phone ?? 'N/A' }}<br>
                    @if(!empty($order->address_snapshot['gstin']) || !empty($order->customer?->gstin))
                        <strong>Customer GSTIN:</strong> {{ $order->address_snapshot['gstin'] ?? $order->customer?->gstin }}<br>
                    @endif
                    <span class="text-muted" style="font-size: 7.5pt;">State of Delivery: {{ $customerState }} (Code: {{ $placeOfSupplyCode }})</span>
                </div>
            </td>
            <td width="50%" style="padding-left: 6px; vertical-align: top;">
                <div class="meta-card">
                    <div class="meta-card-title">Order &amp; Payment Details:</div>
                    <strong>Order Reference:</strong> #{{ $order->order_number }}<br>
                    <strong>Order Placed:</strong> {{ $order->created_at->format('d M Y, h:i A') }}<br>
                    <strong>Payment Gateway:</strong> {{ strtoupper($order->payments->first()?->gateway ?? ($order->payment_method === \App\Enums\PaymentMethod::COD ? 'Cash on Delivery' : 'Razorpay')) }}<br>
                    <strong>Payment Status:</strong> {{ $order->payment_status->label() }}<br>
                    <strong>Fulfilment Network:</strong> Shiprocket Express Doorstep Dispatch<br>
                    <strong>Package Count:</strong> {{ count($packages) }} Shipment Package(s) (ALLZONE Assured)
                </div>
            </td>
        </tr>
    </table>

    <!-- Product Details Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">#</th>
                <th width="33%">Item Description &amp; Specifications</th>
                <th width="9%" class="text-center">HSN</th>
                <th width="5%" class="text-center">Qty</th>
                <th width="11%" class="text-right">Unit Price</th>
                <th width="8%" class="text-right">Discount</th>
                <th width="10%" class="text-right">Taxable</th>
                <th width="9%" class="text-right">GST Rate</th>
                <th width="11%" class="text-right">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($packages as $pkg)
                @if(count($packages) > 1 || !empty($pkg['seller_name']))
                <tr class="pkg-header-row">
                    <td colspan="9">
                        📦 Package {{ $loop->iteration }} of {{ count($packages) }}: #{{ $pkg['package_number'] }} &bull;
                        {{ $pkg['fulfilment_type'] }} via Shiprocket Courier Network &bull;
                        Merchant Partner: {{ $pkg['seller_name'] }}
                    </td>
                </tr>
                @endif
                @foreach($pkg['items'] as $item)
                    <tr>
                        <td class="text-center">{{ $item['index'] }}</td>
                        <td>
                            <strong>{{ $item['title'] }}</strong>
                            @if(!empty($item['variant_name']))
                                <div style="color: #475569; font-size: 7.5pt;">Variant: {{ $item['variant_name'] }}</div>
                            @endif
                            <div style="color: #64748b; font-size: 7pt;">SKU: {{ $item['sku'] }} &bull; ALLZONE Assured</div>
                        </td>
                        <td class="text-center font-mono">{{ $item['hsn'] }}</td>
                        <td class="text-center">{{ $item['qty'] }}</td>
                        <td class="text-right">&#8377; {{ number_format($item['unit_price'], 2) }}</td>
                        <td class="text-right">
                            @if($item['discount'] > 0)
                                -&#8377; {{ number_format($item['discount'], 2) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right">&#8377; {{ number_format($item['taxable'], 2) }}</td>
                        <td class="text-right">{{ number_format($item['tax_rate'], 1) }}%</td>
                        <td class="text-right" style="font-weight: bold;">&#8377; {{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <!-- Amount in Words Banner -->
    <div class="words-banner">
        <strong>Total Amount Chargeable (in words):</strong> {{ $financials['totalInWords'] ?? 'Zero Rupees Only' }}
    </div>

    <!-- Tax Analysis & Payment Summary Section -->
    <table class="financial-summary-table" width="100%">
        <tr>
            <!-- GST Tax Analysis Schedule (Left) -->
            <td width="54%" style="vertical-align: top; padding-right: 8px;">
                <div style="font-size: 7.5pt; font-weight: bold; color: #1e3a8a; text-transform: uppercase; margin-bottom: 4px;">
                    GST Tax Analysis &amp; Breakdown
                </div>
                <table class="tax-analysis-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">HSN</th>
                            <th>Taxable</th>
                            @if($isIntraState)
                                <th>CGST (9%)</th>
                                <th>SGST (9%)</th>
                            @else
                                <th>IGST (18%)</th>
                            @endif
                            <th>Total GST</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($taxAnalysis as $taxRow)
                            <tr>
                                <td style="text-align: left; font-family: monospace;">{{ $taxRow['hsn'] }}</td>
                                <td>&#8377; {{ number_format($taxRow['taxable'], 2) }}</td>
                                @if($isIntraState)
                                    <td>&#8377; {{ number_format($taxRow['cgst'], 2) }}</td>
                                    <td>&#8377; {{ number_format($taxRow['sgst'], 2) }}</td>
                                @else
                                    <td>&#8377; {{ number_format($taxRow['igst'], 2) }}</td>
                                @endif
                                <td style="font-weight: bold;">&#8377; {{ number_format($taxRow['total_tax'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr style="background-color: #f8fafc; font-weight: bold;">
                            <td style="text-align: left;">Total</td>
                            <td>&#8377; {{ number_format($financials['taxableAmount'], 2) }}</td>
                            @if($isIntraState)
                                <td>&#8377; {{ number_format($financials['cgst'], 2) }}</td>
                                <td>&#8377; {{ number_format($financials['sgst'], 2) }}</td>
                            @else
                                <td>&#8377; {{ number_format($financials['igst'], 2) }}</td>
                            @endif
                            <td>&#8377; {{ number_format($financials['orderTax'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div style="font-size: 6.8pt; color: #64748b; margin-top: 4px;">
                    * Product selling prices are inclusive of GST in accordance with Indian tax statutes.
                </div>
            </td>

            <!-- Financials Summary (Right) -->
            <td width="46%" style="vertical-align: top; padding-left: 8px;">
                <div class="totals-box">
                    <table class="totals-table">
                        <tr>
                            <td class="text-muted">Gross Items Subtotal:</td>
                            <td class="text-right" style="font-weight: 600;">&#8377; {{ number_format($financials['subtotal'], 2) }}</td>
                        </tr>
                        @if($financials['discount'] > 0)
                        <tr>
                            <td style="color: #15803d;">Instant Coupon Savings:</td>
                            <td class="text-right" style="color: #15803d; font-weight: 600;">-&#8377; {{ number_format($financials['discount'], 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Doorstep Delivery Fee:</td>
                            <td class="text-right">
                                {{ $financials['deliveryFee'] == 0 ? 'FREE' : ('₹ ' . number_format($financials['deliveryFee'], 2)) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Taxable Value (Pre-Tax Base):</td>
                            <td class="text-right">&#8377; {{ number_format($financials['taxableAmount'], 2) }}</td>
                        </tr>
                        @if($isIntraState)
                            <tr>
                                <td class="text-muted">CGST:</td>
                                <td class="text-right">&#8377; {{ number_format($financials['cgst'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">SGST:</td>
                                <td class="text-right">&#8377; {{ number_format($financials['sgst'], 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="text-muted">IGST:</td>
                                <td class="text-right">&#8377; {{ number_format($financials['igst'], 2) }}</td>
                            </tr>
                        @endif
                        <tr class="grand-total">
                            <td>Total Payable:</td>
                            <td class="text-right" style="color: #1e3a8a;">&#8377; {{ number_format($financials['total'], 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- Professional Corporate Footer -->
    <div class="footer-box">
        <table width="100%">
            <tr>
                <td width="70%" style="vertical-align: top;">
                    <strong>Warranty &amp; Replacement Policy:</strong> All consumer electronics and home appliances are backed by official brand warranty and eligible for ALLZONE's 7-Day Replacement Guarantee for transit damage or manufacturing defects.<br>
                    <strong>Declaration:</strong> This is a digitally generated Tax Invoice issued by {{ setting('name', 'ALLZONE') }} (Operated by {{ setting('company_name', 'SK Refrigeration') }}). In accordance with the Information Technology Act, 2000, no physical signature is required.<br>
                    <strong>Customer Support:</strong> Email: {{ setting('support_email', 'support@allzone.in') }} | Hotline: {{ setting('support_phone', '1800-ALLZONE') }} | Web: https://allzone.in
                </td>
                <td width="30%" class="text-right" style="vertical-align: bottom;">
                    <div style="font-weight: bold; color: #1e3a8a; font-size: 8pt;">ALLZONE Marketplace</div>
                    <div style="font-size: 6.8pt; color: #64748b;">Genuine Electronics &bull; Assured Delivery</div>
                    <div style="margin-top: 4px; font-size: 6.5pt; color: #94a3b8;">Printed: {{ now()->format('d M Y, h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Multi-Page Page Numbering Script -->
    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 7.5;
            $color = array(0.45, 0.45, 0.45);
            $y = $pdf->get_height() - 24;
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}  •  INV-" . "{{ $order->order_number }}";
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = ($pdf->get_width() - $width) / 2;
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>

</body>
</html>
