<?php

use App\Http\Controllers\ShippingMethodController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ShoppingCartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SearchHistoryController;
use App\Http\Controllers\ProvincesController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\TransactionController;


;

Route::prefix('payment')->group(function () {
    Route::post('payment', [PaymentController::class, 'createPaymentIntent']);
    Route::post('save', [PaymentController::class, 'saveTransactionHistory']);
});

Route::prefix('user')->group(function () {

    // Lấy toàn bộ thông tin của user
    Route::get('/getAllUsers', [UserController::class, 'getAllUsers'])->name('getAllUsers');




    Route::post('createAdmin', [UserController::class, 'createAdmin'])->name('createAdmin');
    Route::post('createBusiness', [UserController::class, 'createBusiness'])->name('createBusiness');
    Route::post('register', [UserController::class, 'createUser'])->name('register');
    Route::delete('delete/{id}', [UserController::class, 'delete']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::post('login', [UserController::class, 'login'])->name('login');
    Route::get('auth', [GoogleAuthController::class, 'redirectToAuth']);
    Route::get('auth/callback', [GoogleAuthController::class, 'handleAuthCallback']);
    Route::post('loginBusiness', [UserController::class, 'loginBusiness'])->name('loginBusiness');
    Route::post('loginAdmin', [UserController::class, 'loginAdmin'])->name('loginAdmin');
    Route::get('info/{user_id}', [UserController::class, 'info'])->name('info');
    Route::get('auth-total', [UserController::class, 'getTotalUsers'])->name('getTotalUsers');
    Route::get('auth-list/{type_id}', [UserController::class, 'userList'])->name('userList');
    //Địa chỉ của user
    Route::get('address/{user_id}', [UserAddressController::class, 'indexId'])->name('index');
    // thêm địa chỉ user
    Route::post('user-addresses', [UserAddressController::class, 'store']);
    // cập nhật địa chỉ user
    Route::put('address/update/{user_address_id}', [UserAddressController::class, 'update']);
    Route::delete('address/delete/{user_address_id}', [UserAddressController::class, 'destroy']);
});

Route::prefix('public')->group(function () {
    Route::prefix('product')->group(function () {
        Route::get('all', [ProductController::class, 'index']);
        Route::get('show/{id}', [ProductController::class, 'show']);
        Route::delete('delete/{id}', [ProductController::class, 'destroy']);
        Route::post('add', [ProductController::class, 'store']);
        Route::put('update/{id}', [ProductController::class, 'update']);
        //xuất ra 6 sản phẩm mới nhất
        Route::get('latest-products/{categoryId}', [ProductController::class, 'getLatestProductsInCategory'])->name('latest-products');
        // xuất ra 6 sản phẩm best seller
        Route::get('best-selling-products/{categoryId}', [ProductController::class, 'getBestSellingProductsInCategory'])->name('best-selling-products');
        // xuất ra những sản phẩm bởi id của category
        Route::get('indexByCate/{categoryId}', [ProductController::class, 'indexByCategory'])->name('indexByCategory');
        // xuất ra 8 sản phẩm best seller của user
        Route::get('best-selling-user-products/{userId}', [ProductController::class, 'getBestSellingUserProducts'])->name('best-selling-user-products');
        // xuất ra những sản phẩm bởi id của brand và phân trang = 10 sp
        Route::get('indexByBrand/{brandId}', [ProductController::class, 'indexByBrand'])->name('indexByBrand');
        // Lọc sản phẩm theo danh mục và sắp xếp các sản phẩm theo độ đánh giá và lượt bán.
        Route::get('listProductWithCategory/{categoryId}', [ProductController::class, 'listProductWithCategory'])->name('product-with-category');
        // Lọc sản phẩm theo thương hiệu và sắp xếp các sản phẩm theo độ đánh giá và lượt bán.
        Route::get('listProductWithBrand/{categoryId}', [ProductController::class, 'listProductWithBrand']);
        // xuất ra những sản phẩm của user tạo ra( chủ shop)
        Route::get('user/{id}', [ProductController::class, 'indexByUser']);
        Route::get('shop/{id}', [ProductController::class, 'createByShop']);
        // chức năng tìm kiếm sản phẩm theo tên của sản phẩm
        Route::get('/search-products', [SearchHistoryController::class, 'search']);
        // Lọc sản phẩm theo category và brand
        Route::post('/filterByCategoriesAndBrands', [ProductController::class, 'filterByCategoriesAndBrands'])->name('filterByCategoriesAndBrands');
        // Lọc sản phẩm theo giá
        // Route::get('/filter-by-price', [ProductController::class, 'filterByPrice']);

        // Lọc sản phẩm theo đánh giá
        // Route::get('/filter-by-rating', [ProductController::class, 'filterByRating']);

        // Lọc sản phẩm theo địa chỉ của shop
        // Route::get('/filter-by-address', [ProductController::class, 'filterByAddress']);

        //Gợi ý sản phẩm theo lịch sử tìm kiếm gần nhất theo 5 từ khóa gần nhất và mỗi từ khóa ứng vs 5 sản phẩm
        Route::get('recommend/{user_id}', [ProductController::class, 'recommendBaseOnSearch']);
        // Lấy NGẪU NHIÊN 4 danh mục và show 4 sản phẩm bán chạy nhất của 4 danh mục đó
        Route::get('getRandomCategories', [ProductController::class, 'getRandomCategories']);

        Route::prefix('img')->group(function () {
            Route::get('display/{productId}', [ProductImageController::class, 'displayByProductId']);
            Route::post('upload/{productId}', [ProductImageController::class, 'upload']);
            Route::resource('/', ProductImageController::class);
        });
        //hiển thị danh sách sản phẩm theo sắp xếp lượt đánh giá sản phẩm
        Route::get('rating/{shop_id}/{rating?}', [ProductReviewController::class, 'shopReviews']);
        // bình luận sản phẩm
        Route::post('reviews', [ProductReviewController::class, 'store']);
    });

    Route::prefix('revenue')->group(function () {
        // tính tổng doanh thu ngày, tuần, tháng, năm và tỉ lệ so với ngày hôm qua, tuần trước,  tháng trước, năm trước
        Route::get('/total/{shopId}', [RevenueController::class, 'calculateRevenue']);
        // tính tổng số sản phẩm được bán ra trong ngày, tháng, năm và tỉ lệ so với ngày hôm qua, tuần trước,  tháng trước, năm trước
        Route::get('/totalProduct/{shopId}', [RevenueController::class, 'calculateProductSold']);
        //Doanh thu theo từng tháng
        Route::get('/per-month/{shopId}', [RevenueController::class, 'getRevenueByMonth']);
        Route::get('/getSalesByMonth/{shopId}', [RevenueController::class, 'productSalesByMonth']);
    });

    Route::prefix('field')->group(function () {
        // Hiển thị tất cả các lĩnh vực
        Route::get('list', [FieldController::class, 'index']);
        // Hiển thị mỗi trang có 7 lĩnh vực cho admin
        Route::get('listAdmin', [FieldController::class, 'admin']);
        Route::get('/{id}', [FieldController::class, 'show']);
        Route::put('/{id}', [FieldController::class, 'update']);
        Route::delete('/{id}', [FieldController::class, 'delete']);
        Route::post('/addField', [FieldController::class, 'addField']);
    });

    Route::prefix('brand')->group(function () {
        Route::get('list', [BrandController::class, 'index']);
        //Danh sách các brand có field_id
        Route::get('id={fieldId}', [BrandController::class, 'showByld']);
        Route::get('/{id}', [BrandController::class, 'show']);
        Route::put('/{id}', [BrandController::class, 'update']);
        Route::delete('/{id}', [BrandController::class, 'delete']);
        Route::post('/addBrand', [BrandController::class, 'addBrand']);
    });

    Route::prefix('category')->group(function () {
        Route::get('listCategory', [ProductCategoryController::class, 'index']);
        //show category theo field
        Route::get('id={categoryId}', [ProductCategoryController::class, 'showById']);
        //show category theo user
        Route::get('{user_id}', [ProductCategoryController::class, 'showUserCategories']);
        //show category theo user có phân trang
        Route::get('paging/{user_id}', [ProductCategoryController::class, 'showUserCategorieswithP']);
        Route::get('show/{id}', [ProductCategoryController::class, 'show']);
        Route::put('update/{id}', [ProductCategoryController::class, 'update']);
        Route::delete('delete/{id}', [ProductCategoryController::class, 'delete']);
        Route::post('/addCategory', [ProductCategoryController::class, 'addCategory']);
    });

    Route::prefix('cart')->group(function () {
        Route::post('add-to-cart', [ShoppingCartController::class, 'store']);
        Route::get('show/{user_id}', [ShoppingCartController::class, 'index']);
        Route::delete('delete/{product_id}', [ShoppingCartController::class, 'destroy']);
        Route::put('update/{product_id}', [ShoppingCartController::class, 'update']);
    });

    Route::prefix('order')->group(function () {
        // Kiểm tra người dùng đã mua sản phẩm chưa
        Route::get('purchase', [OrderController::class, 'checkPurchase']);
        // Hiển thị danh sách đơn hàng của Customer
        Route::get('list', [OrderController::class, 'getAllOrder']);
        // Tạo đơn hàng
        Route::post('make', [OrderController::class, 'checkout']);
        // Hiển thị chi tiết đơn hàng
        Route::get('details/{order_id}', [OrderController::class, 'getOrderDetails']);
        //show các đơn hàng được đặt của Business
        Route::get('{user_id}', [OrderController::class, 'getSellerOrders']);
        //show don hang duoc tim kiem theo username cua 1 shop
        Route::get('search', [SearchHistoryController::class, 'searchOrdersByUsername']);
        //show cac don hang bị huy
        Route::get('disable/{user_id}', [OrderController::class, 'getDisableOrdersForShop']);
        // Hiển thị các đơn hàng đang được vận chuyển
        Route::get('shipped-orders/{user_id}', [OrderController::class, 'showShippingOrdersByUserId']);
        // Cập Nhật trạng thái đơn hàng
        Route::put('update-order-status/{order_id}', [OrderStatusController::class, 'updateStatus']);
        //tim kiem don hang theo username
        Route::get('{username}/{shop_id}/{order_status?}', [OrderController::class, 'showOrdersbyUsername']);


    });
    Route::prefix('location')->group(function () {
        Route::get('/', [ProvincesController::class, 'index']);
        Route::get('province={provinceId}', [ProvincesController::class, 'getDistricts']);
        Route::get('district={districtId}', [ProvincesController::class, 'getWards']);
    });
});

Route::prefix('shopping-method')->group(function () {
    Route::get('show', [ShippingMethodController::class, 'index']);
});

Route::prefix('notification')->group(function () { // quản lí thông báo
    // Hiển thị danh sách thông báo cho user xem
    Route::get('show', [NotificationController::class, 'index']);
    // Tạo thông báo từ user để gửi user khác
    Route::post('store', [NotificationController::class, 'store']);
    // Cập nhật thông báo nếu đã được đọc
    Route::put('update/{id}', [NotificationController::class, 'update']);
    // xóa thông báo
    Route::delete('delete/{id}', [NotificationController::class, 'destroy']);
});

Route::prefix('transaction')->group(function () {
    // show tất cả giao dịch
    Route::get('getAllTransaction/{user_id}', [TransactionController::class, 'index'])->name('getAllTransaction');
});

Route::prefix('admin')->group(function () {
    // User list
    Route::get('/users/search/{email}', [UserController::class, 'search']);
    // Tìm kiếm field
    Route::get('/fields/searchField', [FieldController::class, 'searchField']);
    // tìm kiếm category
    Route::get('/category/searchCategory', [ProductCategoryController::class, 'searchCategory']);
    // tìm kiếm brand
    Route::get('/brand/searchBrand', [BrandController::class, 'searchBrand']);
    // order list
    Route::get('/orders/search/{username}', [OrderController::class, 'searchOrder']);
    // Product list
    Route::get('/products/search', [ProductController::class, 'searchProduct']);

});