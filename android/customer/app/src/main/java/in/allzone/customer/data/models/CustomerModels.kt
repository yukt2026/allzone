package in.allzone.customer.data.models

import com.google.gson.annotations.SerializedName

data class ApiResponse<T>(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String?,
    @SerializedName("data") val data: T?,
    @SerializedName("errors") val errors: Any?
)

data class PlatformConfig(
    @SerializedName("platform_name") val platformName: String,
    @SerializedName("currency") val currency: String,
    @SerializedName("currency_symbol") val currencySymbol: String,
    @SerializedName("support_phone") val supportPhone: String,
    @SerializedName("support_email") val supportEmail: String
)

data class RegisterRequest(
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String,
    @SerializedName("password") val password: String,
    @SerializedName("device_name") val deviceName: String = "Android Device"
)

data class LoginRequest(
    @SerializedName("login") val login: String,
    @SerializedName("password") val password: String,
    @SerializedName("device_name") val deviceName: String = "Android Device"
)

data class AuthResponse(
    @SerializedName("token") val token: String,
    @SerializedName("token_type") val tokenType: String,
    @SerializedName("user") val user: UserDto
)

data class UserDto(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String?,
    @SerializedName("wallet_balance") val walletBalance: Double
)

data class CustomerProfile(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String?,
    @SerializedName("gender") val gender: String?,
    @SerializedName("dob") val dob: String?,
    @SerializedName("wallet_balance") val walletBalance: Double,
    @SerializedName("stats") val stats: ProfileStats
)

data class ProfileStats(
    @SerializedName("total_orders") val totalOrders: Int,
    @SerializedName("active_orders") val activeOrders: Int,
    @SerializedName("saved_addresses") val savedAddresses: Int,
    @SerializedName("wishlist_count") val wishlistCount: Int
)

data class UpdateProfileRequest(
    @SerializedName("name") val name: String,
    @SerializedName("phone") val phone: String?,
    @SerializedName("gender") val gender: String?,
    @SerializedName("dob") val dob: String?
)

data class AddressModel(
    @SerializedName("id") val id: Long? = null,
    @SerializedName("name") val name: String,
    @SerializedName("phone") val phone: String,
    @SerializedName("line1") val line1: String,
    @SerializedName("line2") val line2: String? = null,
    @SerializedName("landmark") val landmark: String? = null,
    @SerializedName("city") val city: String,
    @SerializedName("state") val state: String,
    @SerializedName("pincode") val pincode: String,
    @SerializedName("type") val type: String = "home",
    @SerializedName("is_default") val isDefault: Boolean = false
)

data class HomePayload(
    @SerializedName("hero_banners") val heroBanners: List<BannerItem>,
    @SerializedName("promo_banners") val promoBanners: List<BannerItem>,
    @SerializedName("categories") val categories: List<CategoryModel>,
    @SerializedName("deals") val deals: List<ProductCardModel>,
    @SerializedName("best_sellers") val bestSellers: List<ProductCardModel>,
    @SerializedName("recommended") val recommended: List<ProductCardModel>
)

data class BannerItem(
    @SerializedName("id") val id: Long,
    @SerializedName("title") val title: String,
    @SerializedName("image_url") val imageUrl: String?,
    @SerializedName("link_url") val linkUrl: String?
)

data class CategoryModel(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("image_url") val imageUrl: String?,
    @SerializedName("products_count") val productsCount: Int? = 0,
    @SerializedName("subcategories") val subcategories: List<CategoryModel>? = null
)

data class ProductCardModel(
    @SerializedName("id") val id: Long,
    @SerializedName("title") val title: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("brand_name") val brandName: String?,
    @SerializedName("category_name") val categoryName: String?,
    @SerializedName("selling_price") val sellingPrice: Double,
    @SerializedName("mrp") val mrp: Double,
    @SerializedName("discount_percent") val discountPercent: Int,
    @SerializedName("thumbnail_url") val thumbnailUrl: String?,
    @SerializedName("rating") val rating: Double,
    @SerializedName("is_in_stock") val isInStock: Boolean
)

data class ProductListResponse(
    @SerializedName("items") val items: List<ProductCardModel>,
    @SerializedName("pagination") val pagination: PaginationInfo
)

data class PaginationInfo(
    @SerializedName("current_page") val currentPage: Int,
    @SerializedName("last_page") val lastPage: Int,
    @SerializedName("total") val total: Int
)

data class ProductDetailModel(
    @SerializedName("id") val id: Long,
    @SerializedName("title") val title: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("description") val description: String?,
    @SerializedName("selling_price") val sellingPrice: Double,
    @SerializedName("mrp") val mrp: Double,
    @SerializedName("images") val images: List<String>,
    @SerializedName("brand") val brand: String?,
    @SerializedName("seller") val seller: String?,
    @SerializedName("is_in_stock") val isInStock: Boolean,
    @SerializedName("variants") val variants: List<VariantDto>,
    @SerializedName("reviews") val reviews: List<ReviewDto>
)

data class VariantDto(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("sku") val sku: String,
    @SerializedName("selling_price") val sellingPrice: Double,
    @SerializedName("mrp") val mrp: Double,
    @SerializedName("is_in_stock") val isInStock: Boolean
)

data class ReviewDto(
    @SerializedName("rating") val rating: Int,
    @SerializedName("user_name") val userName: String,
    @SerializedName("title") val title: String?,
    @SerializedName("body") val body: String?,
    @SerializedName("date") val date: String
)

data class CartResponse(
    @SerializedName("items") val items: List<CartItemDto>,
    @SerializedName("items_count") val itemsCount: Int,
    @SerializedName("subtotal") val subtotal: Double,
    @SerializedName("discount") val discount: Double,
    @SerializedName("total") val total: Double,
    @SerializedName("coupon_code") val couponCode: String?
)

data class CartItemDto(
    @SerializedName("id") val id: Long,
    @SerializedName("variant_id") val variantId: Long,
    @SerializedName("title") val title: String,
    @SerializedName("variant_name") val variantName: String,
    @SerializedName("unit_price") val unitPrice: Double,
    @SerializedName("quantity") val quantity: Int,
    @SerializedName("total_price") val totalPrice: Double,
    @SerializedName("thumbnail_url") val thumbnailUrl: String?
)

data class AddToCartRequest(
    @SerializedName("variant_id") val variantId: Long,
    @SerializedName("quantity") val quantity: Int = 1
)

data class UpdateCartRequest(
    @SerializedName("item_id") val itemId: Long,
    @SerializedName("quantity") val quantity: Int
)

data class WishlistItemModel(
    @SerializedName("id") val id: Long,
    @SerializedName("product_id") val productId: Long,
    @SerializedName("title") val title: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("selling_price") val sellingPrice: Double,
    @SerializedName("mrp") val mrp: Double,
    @SerializedName("thumbnail_url") val thumbnailUrl: String?,
    @SerializedName("is_in_stock") val isInStock: Boolean
)

data class WishlistToggleResponse(
    @SerializedName("in_wishlist") val inWishlist: Boolean
)

data class CheckoutSummary(
    @SerializedName("subtotal") val subtotal: Double,
    @SerializedName("discount") val discount: Double,
    @SerializedName("delivery_fee") val deliveryFee: Double,
    @SerializedName("total_payable") val totalPayable: Double,
    @SerializedName("wallet_balance") val walletBalance: Double,
    @SerializedName("selected_address") val selectedAddress: AddressModel?,
    @SerializedName("payment_options") val paymentOptions: List<PaymentOptionDto>
)

data class PaymentOptionDto(
    @SerializedName("id") val id: String,
    @SerializedName("title") val title: String,
    @SerializedName("subtitle") val subtitle: String?,
    @SerializedName("is_available") val isAvailable: Boolean
)

data class PlaceOrderRequest(
    @SerializedName("address_id") val addressId: Long,
    @SerializedName("payment_method") val paymentMethod: String, // "online", "cod", "wallet"
    @SerializedName("notes") val notes: String? = null
)

data class PlaceOrderResponse(
    @SerializedName("order_number") val orderNumber: String,
    @SerializedName("total") val total: Double,
    @SerializedName("status") val status: String,
    @SerializedName("payment_status") val paymentStatus: String,
    @SerializedName("payment_method") val paymentMethod: String,
    @SerializedName("payment_gateway") val paymentGateway: PaymentGatewayPayload?
)

data class PaymentGatewayPayload(
    @SerializedName("gateway_order_id") val gatewayOrderId: String?,
    @SerializedName("key_id") val keyId: String?,
    @SerializedName("amount") val amount: Double?,
    @SerializedName("currency") val currency: String?
)

data class OrdersResponse(
    @SerializedName("orders") val orders: List<OrderSummaryDto>,
    @SerializedName("pagination") val pagination: PaginationInfo
)

data class OrderSummaryDto(
    @SerializedName("order_number") val orderNumber: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("status") val status: String,
    @SerializedName("status_label") val statusLabel: String,
    @SerializedName("payment_status") val paymentStatus: String,
    @SerializedName("payment_method") val paymentMethod: String,
    @SerializedName("total") val total: Double,
    @SerializedName("packages") val packages: List<PackageSummaryDto>
)

data class PackageSummaryDto(
    @SerializedName("package_number") val packageNumber: String,
    @SerializedName("seller_name") val sellerName: String,
    @SerializedName("status") val status: String,
    @SerializedName("items_count") val itemsCount: Int
)

data class OrderDetailModel(
    @SerializedName("order_number") val orderNumber: String,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("status") val status: String,
    @SerializedName("total") val total: Double,
    @SerializedName("delivery_fee") val deliveryFee: Double,
    @SerializedName("address") val address: Map<String, Any>?,
    @SerializedName("packages") val packages: List<PackageDetailDto>
)

data class PackageDetailDto(
    @SerializedName("package_number") val packageNumber: String,
    @SerializedName("status") val status: String,
    @SerializedName("courier") val courier: String?,
    @SerializedName("awb") val awb: String?,
    @SerializedName("tracking") val tracking: List<TrackingMilestoneDto>,
    @SerializedName("items") val items: List<OrderItemDto>
)

data class TrackingMilestoneDto(
    @SerializedName("status") val status: String,
    @SerializedName("location") val location: String?,
    @SerializedName("time") val time: String
)

data class OrderItemDto(
    @SerializedName("id") val id: Long,
    @SerializedName("title") val title: String,
    @SerializedName("qty") val qty: Int,
    @SerializedName("unit_price") val unitPrice: Double
)

data class NotificationModel(
    @SerializedName("id") val id: Long,
    @SerializedName("event_key") val eventKey: String,
    @SerializedName("content") val content: String,
    @SerializedName("sent_at") val sentAt: String
)
