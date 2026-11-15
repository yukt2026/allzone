package in.allzone.customer.data.api

import in.allzone.customer.data.models.*
import retrofit2.Response
import retrofit2.http.*

/**
 * ALLZONE Customer Mobile App Retrofit REST API Interface.
 * Connects directly to ALLZONE Laravel Backend (/api/v1/).
 */
interface AllZoneCustomerApi {

    // 1. App Configuration & Metadata
    @GET("config")
    suspend fun getConfig(): Response<ApiResponse<PlatformConfig>>

    // 2. Authentication
    @POST("customer/register")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse<AuthResponse>>

    @POST("customer/login")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<AuthResponse>>

    @POST("customer/logout")
    suspend fun logout(): Response<ApiResponse<Any>>

    // 3. Customer Profile & Addresses
    @GET("customer/profile")
    suspend fun getProfile(): Response<ApiResponse<CustomerProfile>>

    @PUT("customer/profile")
    suspend fun updateProfile(@Body request: UpdateProfileRequest): Response<ApiResponse<CustomerProfile>>

    @GET("customer/addresses")
    suspend fun getAddresses(): Response<ApiResponse<List<AddressModel>>>

    @POST("customer/addresses")
    suspend fun saveAddress(@Body address: AddressModel): Response<ApiResponse<AddressModel>>

    @DELETE("customer/addresses/{id}")
    suspend fun deleteAddress(@Path("id") id: Long): Response<ApiResponse<Any>>

    // 4. Catalogue: Home, Categories, Products & Search
    @GET("home")
    suspend fun getHomePayload(): Response<ApiResponse<HomePayload>>

    @GET("categories")
    suspend fun getCategories(): Response<ApiResponse<List<CategoryModel>>>

    @GET("products")
    suspend fun getProducts(
        @Query("q") query: String? = null,
        @Query("category_id") categoryId: Long? = null,
        @Query("brand_id") brandId: Long? = null,
        @Query("sort") sort: String? = "latest",
        @Query("min_price") minPrice: Double? = null,
        @Query("max_price") maxPrice: Double? = null,
        @Query("page") page: Int = 1
    ): Response<ApiResponse<ProductListResponse>>

    @GET("products/{slug}")
    suspend fun getProductDetail(@Path("slug") slug: String): Response<ApiResponse<ProductDetailModel>>

    // 5. Cart & Wishlist
    @GET("customer/cart")
    suspend fun getCart(): Response<ApiResponse<CartResponse>>

    @POST("customer/cart/add")
    suspend fun addToCart(@Body item: AddToCartRequest): Response<ApiResponse<CartResponse>>

    @PUT("customer/cart/update")
    suspend fun updateCartItem(@Body item: UpdateCartRequest): Response<ApiResponse<CartResponse>>

    @DELETE("customer/cart/items/{itemId}")
    suspend fun removeCartItem(@Path("itemId") itemId: Long): Response<ApiResponse<CartResponse>>

    @GET("customer/wishlist")
    suspend fun getWishlist(): Response<ApiResponse<List<WishlistItemModel>>>

    @POST("customer/wishlist/toggle")
    suspend fun toggleWishlist(@Body request: Map<String, Long>): Response<ApiResponse<WishlistToggleResponse>>

    // 6. Checkout & Order Placement
    @GET("customer/checkout/summary")
    suspend fun getCheckoutSummary(@Query("address_id") addressId: Long? = null): Response<ApiResponse<CheckoutSummary>>

    @POST("customer/checkout/place-order")
    suspend fun placeOrder(@Body request: PlaceOrderRequest): Response<ApiResponse<PlaceOrderResponse>>

    // 7. Orders & Live Tracking
    @GET("customer/orders")
    suspend fun getOrders(@Query("page") page: Int = 1): Response<ApiResponse<OrdersResponse>>

    @GET("customer/orders/{orderNumber}")
    suspend fun getOrderDetail(@Path("orderNumber") orderNumber: String): Response<ApiResponse<OrderDetailModel>>

    @POST("customer/orders/{orderNumber}/cancel")
    suspend fun cancelOrder(@Path("orderNumber") orderNumber: String): Response<ApiResponse<Any>>

    // 8. Push Notifications
    @POST("customer/fcm-token")
    suspend fun registerFcmToken(@Body request: Map<String, String>): Response<ApiResponse<Any>>

    @GET("customer/notifications")
    suspend fun getNotifications(): Response<ApiResponse<List<NotificationModel>>>
}
