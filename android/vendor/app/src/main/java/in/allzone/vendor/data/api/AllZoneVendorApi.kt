package in.allzone.vendor.data.api

import in.allzone.vendor.data.models.*
import retrofit2.Response
import retrofit2.http.*

/**
 * ALLZONE Vendor / Seller Partner Mobile App Retrofit REST API.
 * Connects directly to ALLZONE Laravel Backend (/api/v1/vendor/).
 */
interface AllZoneVendorApi {

    // 1. Authentication & Onboarding
    @POST("vendor/login")
    suspend fun login(@Body request: VendorLoginRequest): Response<ApiResponse<VendorAuthResponse>>

    @POST("vendor/register")
    suspend fun register(@Body request: VendorRegisterRequest): Response<ApiResponse<VendorAuthResponse>>

    @POST("vendor/logout")
    suspend fun logout(): Response<ApiResponse<Any>>

    // 2. Dashboard KPIs & Store Profile
    @GET("vendor/dashboard")
    suspend fun getDashboard(): Response<ApiResponse<VendorDashboardDto>>

    @GET("vendor/profile")
    suspend fun getProfile(): Response<ApiResponse<VendorProfileDto>>

    @GET("vendor/store")
    suspend fun getStore(): Response<ApiResponse<StoreDto>>

    @PUT("vendor/store")
    suspend fun updateStore(@Body request: UpdateStoreRequest): Response<ApiResponse<StoreDto>>

    // 3. Products & Inventory Management
    @GET("vendor/products")
    suspend fun getProducts(@Query("page") page: Int = 1): Response<ApiResponse<VendorProductsResponse>>

    @GET("vendor/inventory")
    suspend fun getInventory(@Query("page") page: Int = 1): Response<ApiResponse<VendorInventoryResponse>>

    @POST("vendor/inventory/update")
    suspend fun updateStock(@Body request: UpdateStockRequest): Response<ApiResponse<UpdateStockResponse>>

    // 4. Order Management & Processing
    @GET("vendor/orders")
    suspend fun getOrders(
        @Query("status") status: String? = null,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<VendorOrdersResponse>>

    @POST("vendor/orders/{id}/status")
    suspend fun updateOrderStatus(
        @Path("id") orderId: Long,
        @Body request: UpdateOrderStatusRequest
    ): Response<ApiResponse<UpdateOrderStatusResponse>>

    // 5. Earnings, Commissions & Payout Withdrawals
    @GET("vendor/earnings")
    suspend fun getEarnings(): Response<ApiResponse<VendorEarningsDto>>

    @GET("vendor/commissions")
    suspend fun getCommissions(@Query("page") page: Int = 1): Response<ApiResponse<VendorCommissionsResponse>>

    @POST("vendor/withdrawals")
    suspend fun requestWithdrawal(@Body request: RequestWithdrawalPayload): Response<ApiResponse<Any>>

    // 6. Notifications
    @GET("vendor/notifications")
    suspend fun getNotifications(): Response<ApiResponse<List<VendorNotificationDto>>>
}
