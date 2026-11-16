<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class OrderPdfService
{
    /**
     * Generate Tax Invoice PDF for an order.
     */
    public function generateInvoicePdf(Order $order, bool $stream = false): Response
    {
        $invoiceData = $this->prepareInvoiceData($order);

        $pdf = Pdf::loadView('pdf.invoice', $invoiceData)
            ->setPaper('a4', 'portrait');

        if ($stream || request()?->query('stream') || request()?->query('preview')) {
            return $pdf->stream("Tax-Invoice-{$order->order_number}.pdf");
        }

        return $pdf->download("Tax-Invoice-{$order->order_number}.pdf");
    }

    /**
     * Render raw PDF binary data (useful for programmatic testing, attachments, and archiving).
     */
    public function getInvoicePdfRaw(Order $order): string
    {
        $invoiceData = $this->prepareInvoiceData($order);

        $pdf = Pdf::loadView('pdf.invoice', $invoiceData)
            ->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    /**
     * Prepare structured, audited tax and business data for the invoice template.
     *
     * @param Order $order
     * @return array<string, mixed>
     */
    public function prepareInvoiceData(Order $order): array
    {
        $order->loadMissing([
            'customer.user',
            'payments',
            'fulfilmentOrders.items.variant.product',
            'fulfilmentOrders.store',
            'fulfilmentOrders.seller',
        ]);

        $snapshot = $order->address_snapshot ?? [];
        $customerState = trim((string) ($snapshot['state'] ?? 'Karnataka'));
        $originState = 'Karnataka'; // SK Refrigeration registered platform state
        $isIntraState = (strcasecmp($customerState, $originState) === 0 || empty($customerState));

        $stateCodes = [
            'jammu and kashmir' => '01',
            'himachal pradesh' => '02',
            'punjab' => '03',
            'chandigarh' => '04',
            'uttarakhand' => '05',
            'haryana' => '06',
            'delhi' => '07',
            'rajasthan' => '08',
            'uttar pradesh' => '09',
            'bihar' => '10',
            'sikkim' => '11',
            'arunachal pradesh' => '12',
            'nagaland' => '13',
            'manipur' => '14',
            'mizoram' => '15',
            'tripura' => '16',
            'meghalaya' => '17',
            'assam' => '18',
            'west bengal' => '19',
            'jharkhand' => '20',
            'odisha' => '21',
            'chhattisgarh' => '22',
            'madhya pradesh' => '23',
            'gujarat' => '24',
            'daman and diu' => '25',
            'dadra and nagar haveli' => '26',
            'maharashtra' => '27',
            'andhra pradesh' => '37',
            'karnataka' => '29',
            'goa' => '30',
            'lakshadweep' => '31',
            'kerala' => '32',
            'tamil nadu' => '33',
            'puducherry' => '34',
            'andaman and nicobar islands' => '35',
            'telangana' => '36',
            'ladakh' => '38',
        ];
        $placeOfSupplyCode = $stateCodes[strtolower($customerState)] ?? '29';

        // Financials Reconciliation
        $subtotal = (float) $order->subtotal;
        $discount = (float) $order->discount;
        $deliveryFee = (float) $order->delivery_fee;
        $orderTax = (float) $order->tax;
        $total = (float) $order->total;

        // Detect if the order was computed inclusive or exclusive of GST
        // Real storefront checkout: total = max(0, subtotal - discount + deliveryFee) where subtotal is tax-inclusive
        // Legacy/demo checkout: total = subtotal - discount + deliveryFee + tax
        $inclusiveDiff = abs(($subtotal - $discount + $deliveryFee) - $total);
        $exclusiveDiff = abs(($subtotal - $discount + $deliveryFee + $orderTax) - $total);
        $isTaxInclusive = ($inclusiveDiff <= 0.05) || ($orderTax == 0 && $inclusiveDiff < $exclusiveDiff);

        // Taxable Base Calculation
        $taxableAmount = $isTaxInclusive
            ? max(0.0, round($subtotal - $discount - $orderTax, 2))
            : max(0.0, round($subtotal - $discount, 2));

        if ($isIntraState) {
            $cgst = round($orderTax / 2, 2);
            $sgst = round($orderTax - $cgst, 2);
            $igst = 0.00;
        } else {
            $cgst = 0.00;
            $sgst = 0.00;
            $igst = $orderTax;
        }

        // Document Status & Classification
        $paymentStatus = $order->payment_status;
        $isPaid = ($paymentStatus === PaymentStatus::PAID);
        $isCod = ($order->payment_method === PaymentMethod::COD);
        $isRefunded = ($paymentStatus === PaymentStatus::REFUNDED || $paymentStatus === PaymentStatus::PARTIALLY_REFUNDED);

        if ($isRefunded) {
            $invoiceTitle = 'CREDIT NOTE / REFUND INVOICE';
            $statusBadge = 'REFUNDED';
            $badgeColor = '#7e22ce'; // purple
            $badgeBg = '#f3e8ff';
        } elseif ($isPaid) {
            $invoiceTitle = 'TAX INVOICE';
            $statusBadge = 'PAID';
            $badgeColor = '#15803d'; // green
            $badgeBg = '#dcfce7';
        } elseif ($isCod) {
            $invoiceTitle = 'TAX INVOICE (CASH ON DELIVERY)';
            $statusBadge = 'PAYMENT DUE ON DELIVERY';
            $badgeColor = '#1d4ed8'; // blue
            $badgeBg = '#dbeafe';
        } else {
            $invoiceTitle = 'PROFORMA INVOICE / ORDER RECEIPT';
            $statusBadge = 'PAYMENT PENDING';
            $badgeColor = '#b45309'; // amber
            $badgeBg = '#fef3c7';
        }

        $invoiceNumber = 'INV-' . $order->order_number;
        $totalInWords = $this->numberToWordsIndian($total);

        // Build packages and line items
        $packages = [];
        $taxAnalysisMap = [];
        $itemIndex = 1;

        foreach ($order->fulfilmentOrders as $fo) {
            $itemsData = [];
            foreach ($fo->items as $item) {
                $product = $item->variant?->product;
                $hsn = $product?->hsn_code ?: '8418'; // Standard Indian electronics HSN fallback
                $qty = max(1, (int) $item->qty);
                $unitPrice = (float) $item->price;
                $lineDiscount = (float) ($item->discount ?? 0);
                $lineGross = (float) $item->total;
                $taxRate = (float) ($product?->tax_percent ?: 18.00);

                if ($isTaxInclusive) {
                    $lineTax = ($lineGross > 0) ? round($lineGross * ($taxRate / (100 + $taxRate)), 2) : 0.00;
                    $lineTaxable = round($lineGross - $lineTax, 2);
                } else {
                    $lineTaxable = $lineGross;
                    $lineTax = round($lineTaxable * ($taxRate / 100), 2);
                }

                $itemsData[] = [
                    'index' => $itemIndex++,
                    'title' => $item->title,
                    'variant_name' => ($item->variant_name && $item->variant_name !== 'Default') ? $item->variant_name : null,
                    'sku' => $item->sku,
                    'hsn' => $hsn,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount' => $lineDiscount,
                    'taxable' => $lineTaxable,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total' => $isTaxInclusive ? $lineGross : ($lineTaxable + $lineTax),
                ];

                // Aggregate Tax Analysis by HSN & Tax Rate
                $hsnKey = "{$hsn}_{$taxRate}";
                if (!isset($taxAnalysisMap[$hsnKey])) {
                    $taxAnalysisMap[$hsnKey] = [
                        'hsn' => $hsn,
                        'rate' => $taxRate,
                        'taxable' => 0.00,
                        'cgst' => 0.00,
                        'sgst' => 0.00,
                        'igst' => 0.00,
                        'total_tax' => 0.00,
                    ];
                }
                $taxAnalysisMap[$hsnKey]['taxable'] += $lineTaxable;
                $taxAnalysisMap[$hsnKey]['total_tax'] += $lineTax;
                if ($isIntraState) {
                    $c = round($lineTax / 2, 2);
                    $s = round($lineTax - $c, 2);
                    $taxAnalysisMap[$hsnKey]['cgst'] += $c;
                    $taxAnalysisMap[$hsnKey]['sgst'] += $s;
                } else {
                    $taxAnalysisMap[$hsnKey]['igst'] += $lineTax;
                }
            }

            $packages[] = [
                'fo' => $fo,
                'package_number' => $fo->fulfilment_order_number,
                'fulfilment_type' => $fo->fulfilment_type->label(),
                'seller_name' => $fo->seller?->business_name ?? 'ALLZONE Official Direct',
                'store_name' => $fo->store?->name,
                'items' => $itemsData,
            ];
        }

        return [
            'order' => $order,
            'invoiceNumber' => $invoiceNumber,
            'invoiceTitle' => $invoiceTitle,
            'statusBadge' => $statusBadge,
            'badgeColor' => $badgeColor,
            'badgeBg' => $badgeBg,
            'isPaid' => $isPaid,
            'isCod' => $isCod,
            'isRefunded' => $isRefunded,
            'customerState' => $customerState,
            'placeOfSupplyCode' => $placeOfSupplyCode,
            'isIntraState' => $isIntraState,
            'isTaxInclusive' => $isTaxInclusive,
            'financials' => [
                'subtotal' => $subtotal,
                'discount' => $discount,
                'deliveryFee' => $deliveryFee,
                'orderTax' => $orderTax,
                'taxableAmount' => $taxableAmount,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'total' => $total,
                'totalInWords' => $totalInWords,
            ],
            'packages' => $packages,
            'taxAnalysis' => array_values($taxAnalysisMap),
        ];
    }

    /**
     * Convert an amount into Indian Rupees in words.
     */
    public function numberToWordsIndian(float $amount): string
    {
        $amount = round($amount, 2);
        $paise = (int) round(($amount - floor($amount)) * 100);
        $num = (int) floor($amount);

        if ($num === 0 && $paise === 0) {
            return 'Zero Rupees Only';
        }

        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
            20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty',
            60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        ];

        $convertBelowThousand = function (int $n) use ($words): string {
            $out = '';
            if ($n >= 100) {
                $hundreds = (int) floor($n / 100);
                $out .= $words[$hundreds] . ' Hundred ';
                $n %= 100;
            }
            if ($n > 0) {
                if ($n < 20) {
                    $out .= $words[$n] . ' ';
                } else {
                    $tens = (int) (floor($n / 10) * 10);
                    $units = $n % 10;
                    $out .= $words[$tens] . ' ';
                    if ($units > 0) {
                        $out .= $words[$units] . ' ';
                    }
                }
            }
            return trim($out);
        };

        $crores = (int) floor($num / 10000000);
        $num %= 10000000;
        $lakhs = (int) floor($num / 100000);
        $num %= 100000;
        $thousands = (int) floor($num / 1000);
        $num %= 1000;
        $remainder = $num;

        $parts = [];
        if ($crores > 0) {
            $parts[] = $convertBelowThousand($crores) . ' Crore';
        }
        if ($lakhs > 0) {
            $parts[] = $convertBelowThousand($lakhs) . ' Lakh';
        }
        if ($thousands > 0) {
            $parts[] = $convertBelowThousand($thousands) . ' Thousand';
        }
        if ($remainder > 0) {
            $parts[] = $convertBelowThousand($remainder);
        }

        $result = 'Indian Rupees ' . implode(' ', $parts);
        if ($paise > 0) {
            $result .= ' and ' . $convertBelowThousand($paise) . ' Paise';
        }
        $result .= ' Only';

        return $result;
    }

    /**
     * Generate Packing Slip PDF for a fulfilment package.
     */
    public function generatePackingSlipPdf(FulfilmentOrder $fo): Response
    {
        $fo->loadMissing([
            'order.customer.user',
            'items.variant.product',
            'store',
            'seller',
            'inventoryLocation',
        ]);

        $pdf = Pdf::loadView('pdf.packing-slip', [
            'fo' => $fo,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Packing-Slip-{$fo->fulfilment_order_number}.pdf");
    }

    /**
     * Generate Shipping Label PDF for courier / rider dispatch.
     */
    public function generateShippingLabelPdf(FulfilmentOrder $fo): Response
    {
        $fo->loadMissing([
            'order.customer.user',
            'store',
            'seller',
        ]);

        $pdf = Pdf::loadView('pdf.shipping-label', [
            'fo' => $fo,
        ])->setPaper([0, 0, 288, 432], 'portrait'); // Standard 4x6 inch thermal shipping label

        return $pdf->download("Shipping-Label-{$fo->fulfilment_order_number}.pdf");
    }
}
