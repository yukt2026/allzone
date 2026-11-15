<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About ALLZONE',
                'slug' => 'about-us',
                'meta_title' => 'About Us - ALLZONE (SK Refrigeration)',
                'meta_description' => 'Learn more about ALLZONE, an enterprise multi-vendor e-commerce marketplace operated by SK Refrigeration, connecting local stores with national commerce.',
                'content' => "Welcome to ALLZONE – Your Premier Multi-Vendor E-Commerce Marketplace (A Unit of SK Refrigeration).\n\nAt ALLZONE, we bridge the gap between traditional retail and modern digital commerce. Our unified platform connects customers with verified local neighborhood stores for 10-30 minute quick deliveries while providing nationwide access to trusted brands and authorized merchants.\n\nOur Mission:\nTo empower retailers with cutting-edge digital inventory tools, automated cargo logistics, and instant fulfillment capabilities while giving shoppers unparalleled speed, genuine products, and unbeatable prices.\n\nWhy ALLZONE?\n- Hyperlocal Speed: Partnering with local stores for under 30-minute delivery.\n- 100% Authenticity: Direct partnerships with authorized brand distributors.\n- Omnichannel Flexibility: Doorstep delivery, store pickup, and nationwide freight shipping.\n- Secure Payments: Protected transactions via UPI, Cards, NetBanking, and Cash on Delivery.",
                'is_active' => true,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'meta_title' => 'Privacy Policy - ALLZONE',
                'meta_description' => 'Read ALLZONE\'s privacy policy regarding customer data protection, tracking, and compliance.',
                'content' => "ALLZONE / SK Refrigeration (\"ALLZONE\", \"we\", \"us\", or \"our\") is committed to protecting your personal information and privacy rights.\n\n1. Information We Collect:\n- Personal Identification: Name, phone number, email address, and delivery addresses.\n- Geolocation Data: Precise or approximate GPS coordinates collected to locate nearby stores and calculate dynamic delivery times.\n- Transaction Information: Order history, items purchased, and payment method details (we do not store raw card numbers or CVVs).\n\n2. How We Use Your Data:\n- To fulfill and deliver orders from nearby partner stores and warehouses.\n- To provide live order tracking and rider updates.\n- To detect fraud and protect the security of our platform.\n- To send important transactional notifications regarding your orders.\n\n3. Data Protection & Sharing:\nWe never sell your personal data. We only share necessary delivery information (address and contact phone) with verified fulfillment partners and delivery riders for the sole purpose of delivering your orders.\n\n4. Your Rights:\nYou may request access to, correction of, or deletion of your personal account data at any time by contacting our privacy team at privacy@allzone.in.",
                'is_active' => true,
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms-of-service',
                'meta_title' => 'Terms & Conditions of Service - ALLZONE',
                'meta_description' => 'Terms and conditions governing the use of ALLZONE web and mobile applications.',
                'content' => "Please read these Terms of Service carefully before accessing or using ALLZONE.\n\n1. Acceptance of Terms:\nBy using ALLZONE's website, application, or services, you agree to be bound by these Terms and our Privacy Policy.\n\n2. User Accounts:\nYou are responsible for maintaining the confidentiality of your login credentials and for all activities that occur under your account.\n\n3. Orders and Pricing:\nAll orders placed through ALLZONE are subject to seller acceptance and product availability in the respective fulfillment location. Prices and delivery fees are clearly displayed prior to order confirmation.\n\n4. Delivery Commitments:\nHyperlocal delivery estimates (10-30 minutes) are provided based on real-time traffic and store preparation conditions. While we strive to meet every ETA, severe weather or peak rush may cause minor delays.\n\n5. Merchant Marketplace:\nALLZONE facilitates transactions between customers and registered independent sellers. Each merchant is responsible for item quality, warranties, and regulatory compliance.",
                'is_active' => true,
            ],
            [
                'title' => 'Returns & Cancellation Policy',
                'slug' => 'returns-and-cancellation',
                'meta_title' => 'Returns, Refunds & Cancellations - ALLZONE',
                'meta_description' => 'Information on our hassle-free 7-day return policy and order cancellation guidelines.',
                'content' => "At ALLZONE, customer satisfaction is our top priority. We offer a transparent, customer-first return and refund experience.\n\n1. Cancellation:\n- Orders can be cancelled at any time before dispatch or packaging begins directly from the 'My Orders' section.\n- For Quick Commerce orders, cancellations must be made within 2 minutes of placement due to rapid picking and dispatch.\n\n2. Return Window:\n- General Merchandise & Electronics: 7 days from delivery date.\n- Perishables, Dairy & Fresh Groceries: Immediate inspection upon delivery; returns accepted on the spot or within 24 hours if damaged/spoiled.\n\n3. Refund Processing:\n- Instant refunds for UPI and wallet payments within 2-4 hours of return approval.\n- Credit/Debit cards & NetBanking: 3-5 business days as per banking standards.\n- Cash on Delivery orders: Refund credited directly to your bank account or store wallet.",
                'is_active' => true,
            ],
            [
                'title' => 'Shipping & Delivery Policy',
                'slug' => 'shipping-policy',
                'meta_title' => 'Shipping & Delivery Guidelines - ALLZONE',
                'meta_description' => 'Details on express quick commerce delivery, standard courier delivery, and shipping fees.',
                'content' => "ALLZONE operates a hybrid fulfillment model ensuring lightning-fast delivery across Indian cities.\n\n1. Quick Commerce Delivery (10-30 Mins):\n- Sourced directly from local neighborhood shops and dark store hubs within an 8 km radius.\n- Real-time GPS tracking of your delivery rider from store pickup to your doorstep.\n\n2. Standard Marketplace Delivery (1-3 Days):\n- Dispatched from regional warehouses or brand headquarters via our ALLZONE Cargo logistics network.\n- Comprehensive SMS and WhatsApp updates at every stage of transit.\n\n3. Shipping Charges:\n- Free delivery on orders exceeding the threshold (currently ₹499).\n- Standard nominal delivery fee of ₹40 applies on orders below the threshold.\n- No hidden charges or packaging surcharges.",
                'is_active' => true,
            ],
            [
                'title' => 'Help & Customer Support Desk',
                'slug' => 'help-support',
                'meta_title' => 'Customer Support & Help Center - ALLZONE',
                'meta_description' => 'Get in touch with ALLZONE support for order queries, returns, and merchant assistance.',
                'content' => "Need help with an order or have questions about ALLZONE?\n\nOur customer care team is available 24/7 to assist you.\n\nContact Channels:\n- Toll-Free Hotline: 1800-ALLZONE\n- Email Support: support@allzone.in\n- Merchant Queries: sellers@allzone.in\n- Rider Inquiries: riders@allzone.in\n\nFrequently Asked Questions:\nQ: How do I track my active order?\nA: Go to 'My Orders' in your account menu or click the live tracking link sent via SMS.\n\nQ: Can I change my delivery address after placing an order?\nA: If your order has not been dispatched, you can update the address from your order details page or by contacting customer support.\n\nQ: What payment methods are supported?\nA: We accept UPI (Google Pay, PhonePe, Paytm), Visa/Mastercard/RuPay credit & debit cards, NetBanking, and Cash on Delivery (COD).",
                'is_active' => true,
            ],
            [
                'title' => 'Security & Trust Center',
                'slug' => 'security',
                'meta_title' => 'Security, Fraud Prevention & Trust - ALLZONE',
                'meta_description' => 'How ALLZONE protects your data, verifies sellers, and secures financial transactions.',
                'content' => "At ALLZONE, security and customer trust form the bedrock of our retail experience.\n\n1. Payment Security:\n- End-to-end 256-bit SSL encryption across all web and mobile endpoints.\n- PCI-DSS Level 1 compliant payment gateway integrations.\n- Mandatory Two-Factor Authentication (2FA) and OTP verification for all financial transactions.\n\n2. Seller Verification:\n- Every merchant on the ALLZONE network undergoes rigorous GSTIN, PAN, and bank penny-drop verification before listing items.\n- Random catalog quality audits ensure that counterfeit or substandard products are strictly banned.\n\n3. Delivery Security:\n- Secure OTP-based delivery handshakes ensure your package is handed only to you.\n- Tamper-evident packaging on all marketplace shipments.",
                'is_active' => true,
            ],
            [
                'title' => 'Contact Us',
                'slug' => 'contact-us',
                'meta_title' => 'Contact Us - ALLZONE / SK Refrigeration',
                'meta_description' => 'Official contact details and corporate office address of ALLZONE (SK Refrigeration).',
                'content' => "Get in touch with ALLZONE (A Unit of SK Refrigeration).\n\nCorporate Office:\nALLZONE / SK Refrigeration\nIndia\n\nSupport Email: support@allzone.in\nPress & Media: press@allzone.in\nPartnerships: business@allzone.in\nToll-Free Customer Hotline: 1800-ALLZONE",
                'is_active' => true,
            ],
        ];

        foreach ($pages as $pageData) {
            CmsPage::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}
