<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FulfilmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SellerStatus;
use App\Enums\SellerType;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\FulfilmentOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Seller;
use App\Models\Store;
use App\Models\User;
use App\Services\Order\OrderPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxInvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected Customer $customer;
    protected User $adminUser;
    protected Seller $seller;
    protected Store $store;
    protected OrderPdfService $pdfService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->customerUser = User::factory()->create([
            'name' => 'Aditya Sharma',
            'phone' => '9876543210',
            'status' => UserStatus::ACTIVE,
        ]);
        $this->customerUser->assignRole('customer');
        $this->customer = Customer::factory()->create([
            'user_id' => $this->customerUser->id,
        ]);

        $this->adminUser = User::factory()->create([
            'status' => UserStatus::ACTIVE,
        ]);
        $this->adminUser->assignRole('admin');

        $sellerUser = User::factory()->create();
        $sellerUser->assignRole('seller_owner');

        $this->seller = Seller::create([
            'user_id' => $sellerUser->id,
            'business_name' => 'TechZone Electronics Hub',
            'legal_name' => 'TechZone Retail Pvt Ltd',
            'seller_type' => SellerType::MULTI_STORE,
            'pan' => 'ABCDE1234F',
            'gstin' => '29ABCDE1234F1Z5',
            'status' => SellerStatus::APPROVED,
        ]);

        $this->store = Store::create([
            'seller_id' => $this->seller->id,
            'name' => 'TechZone Flagship Store',
            'slug' => 'techzone-flagship',
            'phone' => '9876543211',
            'email' => 'techzone@allzone.in',
            'address' => '100 Feet Rd, Indiranagar',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'pincode' => '560038',
            'is_active' => true,
        ]);

        $this->pdfService = app(OrderPdfService::class);
    }

    public function test_tax_invoice_data_structure_and_reconciliation(): void
    {
        $order = Order::create([
            'order_number' => 'AZ-TEST-INV-001',
            'customer_id' => $this->customer->id,
            'address_snapshot' => [
                'name' => 'Aditya Sharma',
                'phone' => '9876543210',
                'line1' => 'Flat 402, Royal Residency',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560034',
            ],
            'subtotal' => 65960.00,
            'discount' => 0.00,
            'delivery_fee' => 0.00,
            'tax' => 10061.69,
            'total' => 65960.00, // Tax inclusive
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::CONFIRMED,
        ]);

        $fo = FulfilmentOrder::create([
            'fulfilment_order_number' => 'FO-TEST-INV-001',
            'order_id' => $order->id,
            'seller_id' => $this->seller->id,
            'store_id' => $this->store->id,
            'fulfilment_type' => FulfilmentType::STANDARD,
            'status' => 'confirmed',
            'subtotal' => 65960.00,
            'delivery_fee' => 0.00,
            'tax' => 10061.69,
            'total' => 65960.00,
        ]);

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'title' => 'LG 190L 4-Star Smart Inverter Refrigerator',
            'hsn_code' => '84182100',
            'tax_percent' => 18.00,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'selling_price' => 16490.00,
        ]);

        OrderItem::create([
            'fulfilment_order_id' => $fo->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'title' => $product->title,
            'sku' => 'AZ-SKU-LG190L',
            'qty' => 4,
            'price' => 16490.00,
            'tax' => 10061.69,
            'discount' => 0.00,
            'total' => 65960.00,
        ]);

        $data = $this->pdfService->prepareInvoiceData($order);

        $this->assertEquals('TAX INVOICE', $data['invoiceTitle']);
        $this->assertEquals('PAID', $data['statusBadge']);
        $this->assertTrue($data['isIntraState']);
        $this->assertTrue($data['isTaxInclusive']);
        $this->assertEquals(55898.31, $data['financials']['taxableAmount']);
        $this->assertEquals(5030.85, $data['financials']['cgst']);
        $this->assertEquals(5030.84, $data['financials']['sgst']);
        $this->assertEquals(0.00, $data['financials']['igst']);
        $this->assertEquals(65960.00, $data['financials']['total']);
        $this->assertStringContainsString('Sixty Five Thousand Nine Hundred Sixty', $data['financials']['totalInWords']);

        // Verify PDF output generates valid binary data
        $rawPdf = $this->pdfService->getInvoicePdfRaw($order);
        $this->assertNotEmpty($rawPdf);
        $this->assertStringStartsWith('%PDF', $rawPdf);
    }

    public function test_inter_state_order_calculates_igst(): void
    {
        $order = Order::create([
            'order_number' => 'AZ-TEST-INV-002',
            'customer_id' => $this->customer->id,
            'address_snapshot' => [
                'name' => 'Rohit Verma',
                'phone' => '9811223344',
                'line1' => 'Tower B, Bandra West',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400050',
            ],
            'subtotal' => 20000.00,
            'discount' => 1000.00,
            'delivery_fee' => 0.00,
            'tax' => 2898.31,
            'total' => 19000.00, // Inclusive
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::DELIVERED,
        ]);

        $data = $this->pdfService->prepareInvoiceData($order);

        $this->assertFalse($data['isIntraState']);
        $this->assertEquals('27', $data['placeOfSupplyCode']);
        $this->assertEquals(0.00, $data['financials']['cgst']);
        $this->assertEquals(0.00, $data['financials']['sgst']);
        $this->assertEquals(2898.31, $data['financials']['igst']);
        $this->assertEquals(16101.69, $data['financials']['taxableAmount']);
        $this->assertEquals(19000.00, $data['financials']['total']);
    }

    public function test_cod_pending_and_pending_order_classification(): void
    {
        $codOrder = Order::create([
            'order_number' => 'AZ-TEST-INV-003',
            'customer_id' => $this->customer->id,
            'address_snapshot' => ['state' => 'Karnataka'],
            'subtotal' => 1200.00,
            'discount' => 0.00,
            'delivery_fee' => 40.00,
            'tax' => 183.05,
            'total' => 1240.00,
            'payment_method' => PaymentMethod::COD,
            'payment_status' => PaymentStatus::COD_PENDING,
            'status' => OrderStatus::PLACED,
        ]);

        $codData = $this->pdfService->prepareInvoiceData($codOrder);
        $this->assertEquals('TAX INVOICE (CASH ON DELIVERY)', $codData['invoiceTitle']);
        $this->assertEquals('PAYMENT DUE ON DELIVERY', $codData['statusBadge']);

        $pendingOnlineOrder = Order::create([
            'order_number' => 'AZ-TEST-INV-004',
            'customer_id' => $this->customer->id,
            'address_snapshot' => ['state' => 'Karnataka'],
            'subtotal' => 15000.00,
            'discount' => 0.00,
            'delivery_fee' => 0.00,
            'tax' => 2288.14,
            'total' => 15000.00,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PENDING,
            'status' => OrderStatus::PLACED,
        ]);

        $pendingData = $this->pdfService->prepareInvoiceData($pendingOnlineOrder);
        $this->assertEquals('PROFORMA INVOICE / ORDER RECEIPT', $pendingData['invoiceTitle']);
        $this->assertEquals('PAYMENT PENDING', $pendingData['statusBadge']);
    }

    public function test_customer_can_download_invoice_via_web_route(): void
    {
        $order = Order::create([
            'order_number' => 'AZ-TEST-INV-WEB-001',
            'customer_id' => $this->customer->id,
            'address_snapshot' => [
                'name' => 'Aditya Sharma',
                'line1' => 'Koramangala 4th Block',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'pincode' => '560034',
            ],
            'subtotal' => 5000.00,
            'discount' => 0.00,
            'delivery_fee' => 0.00,
            'tax' => 762.71,
            'total' => 5000.00,
            'payment_method' => PaymentMethod::UPI,
            'payment_status' => PaymentStatus::PAID,
            'status' => OrderStatus::DELIVERED,
        ]);

        $response = $this->actingAs($this->customerUser)
            ->get(route('orders.invoice', $order->order_number));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Tax-Invoice-AZ-TEST-INV-WEB-001.pdf', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_number_to_words_indian_currency_helper(): void
    {
        $this->assertEquals(
            'Indian Rupees Sixty Five Thousand Nine Hundred Sixty Only',
            $this->pdfService->numberToWordsIndian(65960.00)
        );

        $this->assertEquals(
            'Indian Rupees One Lakh Twenty Five Thousand Four Hundred Fifty Only',
            $this->pdfService->numberToWordsIndian(125450.00)
        );

        $this->assertEquals(
            'Indian Rupees Nine Hundred Ninety Nine and Fifty Paise Only',
            $this->pdfService->numberToWordsIndian(999.50)
        );
    }
}
