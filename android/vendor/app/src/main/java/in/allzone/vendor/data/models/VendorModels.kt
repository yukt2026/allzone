package in.allzone.vendor.data.models

import com.google.gson.annotations.SerializedName

data class ApiResponse<T>(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String?,
    @SerializedName("data") val data: T?,
    @SerializedName("errors") val errors: Any?
)

data class VendorLoginRequest(
    @SerializedName("login") val login: String,
    @SerializedName("password") val password: String,
    @SerializedName("device_name") val deviceName: String = "Android Vendor App"
)

data class VendorRegisterRequest(
    @SerializedName("company_name") val companyName: String,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String,
    @SerializedName("password") val password: String,
    @SerializedName("pan_number") val panNumber: String? = null,
    @SerializedName("gstin") val gstin: String? = null,
    @SerializedName("device_name") val deviceName: String = "Android Vendor App"
)

data class VendorAuthResponse(
    @SerializedName("token") val token: String,
    @SerializedName("token_type") val tokenType: String,
    @SerializedName("seller") val seller: SellerInfoDto,
    @SerializedName("user") val user: UserDto
)

data class SellerInfoDto(
    @SerializedName("id") val id: Long,
    @SerializedName("company_name") val companyName: String,
    @SerializedName("status") val status: String,
    @SerializedName("is_approved") val isApproved: Boolean,
    @SerializedName("store_name") val storeName: String?,
    @SerializedName("logo_url") val logoUrl: String?
)

data class UserDto(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("email") val email: String,
    @SerializedName("phone") val phone: String?
)

data class VendorDashboardDto(
    @SerializedName("company_name") val companyName: String,
    @SerializedName("status") val status: String,
    @SerializedName("kpis") val kpis: DashboardKpis,
    @SerializedName("financials") val financials: DashboardFinancials
)

data class DashboardKpis(
    @SerializedName("total_orders") val totalOrders: Int,
    @SerializedName("pending_orders") val pendingOrders: Int,
    @SerializedName("processing_orders") val processingOrders: Int,
    @SerializedName("delivered_orders") val deliveredOrders: Int,
    @SerializedName("delivered_sales") val deliveredSales: Double,
    @SerializedName("low_stock_products") val lowStockProducts: Int
)

data class DashboardFinancials(
    @SerializedName("matured_balance") val maturedBalance: Double,
    @SerializedName("escrow_hold") val escrowHold: Double,
    @SerializedName("settled_total") val settledTotal: Double
)

data class VendorProfileDto(
    @SerializedName("id") val id: Long,
    @SerializedName("company_name") val companyName: String,
    @SerializedName("contact_name") val contactName: String?,
    @SerializedName("email") val email: String?,
    @SerializedName("phone") val phone: String?,
    @SerializedName("gstin") val gstin: String?,
    @SerializedName("pan_number") val panNumber: String?,
    @SerializedName("bank_account_number") val bankAccountNumber: String?,
    @SerializedName("bank_ifsc") val bankIfsc: String?,
    @SerializedName("bank_name") val bankName: String?,
    @SerializedName("status") val status: String,
    @SerializedName("commission_rate_pct") val commissionRatePct: Double
)

data class StoreDto(
    @SerializedName("id") val id: Long,
    @SerializedName("name") val name: String,
    @SerializedName("description") val description: String?,
    @SerializedName("phone") val phone: String?,
    @SerializedName("address") val address: String?,
    @SerializedName("city") val city: String?,
    @SerializedName("state") val state: String?,
    @SerializedName("pincode") val pincode: String?,
    @SerializedName("logo_url") val logoUrl: String?,
    @SerializedName("banner_url") val bannerUrl: String?,
    @SerializedName("is_open") val isOpen: Boolean
)

data class UpdateStoreRequest(
    @SerializedName("name") val name: String,
    @SerializedName("description") val description: String? = null,
    @SerializedName("phone") val phone: String? = null,
    @SerializedName("address") val address: String? = null,
    @SerializedName("city") val city: String? = null,
    @SerializedName("state") val state: String? = null,
    @SerializedName("pincode") val pincode: String? = null
)

data class VendorProductsResponse(
    @SerializedName("products") val products: List<VendorProductItem>,
    @SerializedName("pagination") val pagination: PaginationDto
)

data class VendorProductItem(
    @SerializedName("id") val id: Long,
    @SerializedName("title") val title: String,
    @SerializedName("slug") val slug: String,
    @SerializedName("status") val status: String,
    @SerializedName("category") val category: String?,
    @SerializedName("brand") val brand: String?,
    @SerializedName("selling_price") val sellingPrice: Double,
    @SerializedName("mrp") val mrp: Double,
    @SerializedName("stock") val stock: Int,
    @SerializedName("image_url") val imageUrl: String?
)

data class PaginationDto(
    @SerializedName("current_page") val currentPage: Int,
    @SerializedName("last_page") val lastPage: Int,
    @SerializedName("total") val total: Int
)

data class VendorInventoryResponse(
    @SerializedName("inventory") val inventory: List<InventoryItemDto>,
    @SerializedName("pagination") val pagination: PaginationDto
)

data class InventoryItemDto(
    @SerializedName("inventory_id") val inventoryId: Long,
    @SerializedName("variant_id") val variantId: Long,
    @SerializedName("product_title") val productTitle: String,
    @SerializedName("variant_name") val variantName: String,
    @SerializedName("sku") val sku: String,
    @SerializedName("quantity_on_hand") val quantityOnHand: Int,
    @SerializedName("quantity_reserved") val quantityReserved: Int,
    @SerializedName("quantity_available") val quantityAvailable: Int,
    @SerializedName("is_low_stock") val isLowStock: Boolean
)

data class UpdateStockRequest(
    @SerializedName("variant_id") val variantId: Long,
    @SerializedName("quantity_on_hand") val quantityOnHand: Int
)

data class UpdateStockResponse(
    @SerializedName("variant_id") val variantId: Long,
    @SerializedName("sku") val sku: String,
    @SerializedName("new_quantity_on_hand") val newQuantityOnHand: Int
)

data class VendorOrdersResponse(
    @SerializedName("orders") val orders: List<VendorOrderItemDto>,
    @SerializedName("pagination") val pagination: PaginationDto
)

data class VendorOrderItemDto(
    @SerializedName("id") val id: Long,
    @SerializedName("package_number") val packageNumber: String,
    @SerializedName("order_number") val orderNumber: String?,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("status") val status: String,
    @SerializedName("status_label") val statusLabel: String,
    @SerializedName("items_count") val itemsCount: Int,
    @SerializedName("total_amount") val totalAmount: Double,
    @SerializedName("customer_city") val customerCity: String,
    @SerializedName("items") val items: List<PackageItemDto>
)

data class PackageItemDto(
    @SerializedName("title") val title: String,
    @SerializedName("qty") val qty: Int,
    @SerializedName("price") val price: Double
)

data class UpdateOrderStatusRequest(
    @SerializedName("status") val status: String, // "processing", "ready_for_pickup", "dispatched"
    @SerializedName("note") val note: String? = null
)

data class UpdateOrderStatusResponse(
    @SerializedName("package_number") val packageNumber: String,
    @SerializedName("status") val status: String
)

data class VendorEarningsDto(
    @SerializedName("matured_balance") val maturedBalance: Double,
    @SerializedName("escrow_hold") val escrowHold: Double,
    @SerializedName("settled_total") val settledTotal: Double,
    @SerializedName("settlements") val settlements: List<SettlementRecordDto>
)

data class SettlementRecordDto(
    @SerializedName("id") val id: Long,
    @SerializedName("settlement_number") val settlementNumber: String,
    @SerializedName("amount") val amount: Double,
    @SerializedName("status") val status: String,
    @SerializedName("paid_at") val paidAt: String?
)

data class VendorCommissionsResponse(
    @SerializedName("commissions") val commissions: List<CommissionItemDto>,
    @SerializedName("pagination") val pagination: PaginationDto
)

data class CommissionItemDto(
    @SerializedName("id") val id: Long,
    @SerializedName("package_number") val packageNumber: String?,
    @SerializedName("order_number") val orderNumber: String?,
    @SerializedName("created_at") val createdAt: String,
    @SerializedName("base_amount") val baseAmount: Double,
    @SerializedName("commission_amount") val commissionAmount: Double,
    @SerializedName("gst_on_commission") val gstOnCommission: Double,
    @SerializedName("net_payable") val netPayable: Double
)

data class RequestWithdrawalPayload(
    @SerializedName("amount") val amount: Double,
    @SerializedName("notes") val notes: String? = null
)

data class VendorNotificationDto(
    @SerializedName("id") val id: Long,
    @SerializedName("event_key") val eventKey: String,
    @SerializedName("content") val content: String,
    @SerializedName("sent_at") val sentAt: String
)
