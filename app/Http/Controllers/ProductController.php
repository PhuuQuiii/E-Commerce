<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\product_brand as ProductBrand;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource as ProductResource;
use App\Models\Product;
use App\Models\product_color as ProductColor;
use App\Models\product_size as ProductSize;
use App\Models\user;
use App\Models\search_history;
use App\Models\product_review as ProductReview;
use App\Models\product_image as ProductImage;
use App\Models\product_category as ProductCategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ProductController extends Controller
{
    public function index()
    {
        $product = Product::join('product_category', 'product.product_category_id', '=', 'product_category.product_category_id')
            ->join('product_brand', 'product.product_brand_id', '=', 'product_brand.product_brand_id')
            ->select('product.*', 'product_category.product_category_name', 'product_brand.product_brand_name')
            ->paginate(7);
        $arr = [
            'status' => true,
            'message' => 'Danh sách sản phẩm',
            'data' => $product
        ];

        return response()->json($arr, 200);
    }
    public function indexByCategory($categoryId)
    {
        try {
            // Assuming you have relationships between Product and ProductCategory, and Product and ProductImage
            $products = ProductCategory::findOrFail($categoryId)
                ->products()
                ->with('images') // assuming the relationship name is 'images' in the Product model
                ->get();

            $result = [];

            foreach ($products as $product) {
                $images = $product->images->pluck('image_url')->toArray();

                $result[] = array_merge((new ProductResource($product))->toArray(request()), ['images' => $images]);
            }

            $arr = [
                'status' => true,
                'message' => 'Danh sách sản phẩm theo danh mục',
                'data' => $result,
            ];

            return response()->json($arr, 200);
        } catch (ModelNotFoundException $e) {
            $arr = [
                'status' => false,
                'message' => 'Danh mục sản phẩm không tồn tại hoặc không có sản phẩm nào',
                'data' => null,
            ];

            return response()->json($arr, 404);
        }
    }


    public function indexByBrand($brandId)
    {

        $products = ProductBrand::findOrFail($brandId)
            ->products()
            ->with('images')
            ->paginate(10);


        foreach ($products as $product) {
            $product->images->pluck('image_url');
        }

        if ($products->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Thương hiệu sản phẩm không tồn tại hoặc không có sản phẩm nào',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách sản phẩm theo thương hiệu',
            'data' => $products,
        ], 200);
    }

    public function indexByUser(string $userId)
    {
        try {

            $products = Product::with(['images', 'productSizes', 'productColors', 'productBrand', 'productCategory'])
                ->where('created_by_user_id', $userId)
                ->get();

            if ($products->isEmpty()) {
                $arr = [
                    'status' => false,
                    'message' => 'Người dùng chưa tạo sản phẩm nào',
                    'data' => null,
                ];

                return response()->json($arr, 404);
            }

            $formattedProducts = $products->map(function ($product) {
                $reviews = ProductReview::with('user')
                    ->where('product_id', $product->product_id)
                    ->get();

                $averageRating = $reviews->avg('rating');
                $totalReviews = $reviews->count();

                // Lấy danh sách các URL hình ảnh của sản phẩm
                $imageUrls = $product->images->pluck('image_url');
                $sizes = $product->productSizes->pluck('size_name');
                $colors = $product->productColors->pluck('color_name');

                return [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'created_by_user_id' => $product->created_by_user_id,
                    'product_brand_id' => $product->product_brand_id,
                    'product_category_id' => $product->product_category_id,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'discount_id' => $product->discount_id,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'deleted_at' => $product->deleted_at,
                    'image_urls' => $imageUrls,
                    'sizes' => $sizes,
                    'colors' => $colors,
                    'reviews' => $reviews,
                    'average_rating' => $averageRating,
                    'total_reviews' => $totalReviews,
                ];
            });

            $arr = [
                'status' => true,
                'message' => 'Thông tin sản phẩm của người dùng',
                'data' => $formattedProducts
            ];

            return response()->json($arr, 200);
        } catch (ModelNotFoundException $e) {
            $arr = [
                'status' => false,
                'message' => 'Không tìm thấy người dùng',
                'data' => null,
            ];

            return response()->json($arr, 404);
        }
    }



    public function store(Request $request)
    {
        $input = $request->all();

        // Validate the product information
        $validator = Validator::make($input, [
            'name' => 'required',
            'description' => 'required',
            'created_by_user_id' => 'required',
            'product_brand_id' => 'required',
            'product_category_id' => 'required',
            'price' => 'required',
            'stock' => 'required',
            'discount_id' => 'nullable',
            'image_urls' => 'required|array',
            'image_urls.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'colors' => 'required|array',
            'colors.*' => 'required',
            'sizes' => 'required|array',
            'sizes.*' => 'required',
        ]);

        if ($validator->fails()) {
            $arr = [
                'status' => false,
                'message' => 'Lỗi kiểm tra dữ liệu',
                'data' => $validator->errors()
            ];

            return response()->json($arr, 200);
        }

        // Create the product
        $product = Product::create($request->all());

        // if ($product) {
        //     // Log or handle the error
        //     return response()->json(['error' => $product], 500);
        // }

        $productId = $product->product_id;

        // Upload array of images
        if ($request->hasFile('image_urls')) {
            $imageUrls = $this->uploadMultipleImages($request, $productId);
        }

        // Add array of sizes
        if ($request->has('sizes')) {
            $sizes = $this->addArraySizes($request, $productId);
        }

        // Add array of colors
        if ($request->has('colors')) {
            $colors = $this->addArrayColors($request, $productId);
        }

        $arr = [
            'status' => true,
            'message' => 'Sản phẩm và thông tin liên quan đã được lưu thành công',
            'data' => new ProductResource($product),
            'image_urls' => $imageUrls ?? null, // Include image URLs if available
            'sizes' => $sizes ?? null, // Include sizes if available
            'colors' => $colors ?? null, // Include colors if available
        ];

        return response()->json($arr, 201);
    }

    protected function uploadMultipleImages(Request $request, $productId)
    {
        // Validation for array of images
        $request->validate([
            'image_urls' => 'required|array',
            'image_urls.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $imageUrls = [];

        foreach ($request->file('image_urls') as $key => $image) {
            $imageName = time() . '_' . $key . '.' . $image->extension();
            $destinationPath = base_path('react/src/assets/image');
            $image->move($destinationPath, $imageName);

            $imageUrls[] = $imageName;

            // Create ProductImage within the transaction
            $pi = new ProductImage([
                'product_id' => $productId,
                'image_url' => $imageName,
            ]);
            $pi->save();
        }

        return $imageUrls;
    }

    protected function addArraySizes(Request $request, $productId)
    {
        $request->validate([
            'sizes' => 'required|array',
            'sizes.*' => 'required',
        ]);

        $sizes = [];

        foreach ($request->input('sizes') as $sizeName) {
            $size = new ProductSize([
                'product_id' => $productId,
                'size_name' => $sizeName,
            ]);

            $size->save();

            $sizes[] = $size;
        }

        return $sizes;
    }

    protected function addArrayColors(Request $request, $productId)
    {
        $request->validate([
            'colors' => 'required|array',
            'colors.*' => 'required',
        ]);

        $colors = [];

        foreach ($request->input('colors') as $colorName) {
            $color = new ProductColor([
                'product_id' => $productId,
                'color_name' => $colorName,
            ]);

            $color->save();

            $colors[] = $color;
        }

        return $colors;
    }

    // Hàm xử lý hiển thị thông tin sản phẩm và danh sách ảnh
    public function show(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            $creator = User::find($product->created_by_user_id);
            $reviews = ProductReview::with('user')
                ->where('product_id', $id)
                ->get();

            $averageRating = $reviews->avg('rating');
            $imageUrls = $product->images->pluck('image_url');
            $sizes = ProductSize::where('product_id', $id)->pluck('size_name');
            $colors = ProductColor::where('product_id', $id)->pluck('color_name');
            $numberOfProducts = Product::where('created_by_user_id', $creator->user_id)->count();
            $averageRatingByCreator = ProductReview::whereHas('product', function ($query) use ($creator) {
                $query->where('created_by_user_id', $creator->user_id);
            })->avg('rating');
            $totalReviews = $reviews->count();
            $reviewCounts = $reviews->groupBy('rating')->map->count();

            $formattedReviews = $reviews->map(function ($review) {
                return [
                    'product_review_id' => $review->product_review_id,
                    'user_id' => $review->user_id,
                    'username' => $review->user->username,
                    'full_name' => $review->user->full_name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                ];
            });

            $arr = [
                'status' => true,
                'message' => 'Thông tin sản phẩm',
                'data' => [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'created_by_user_id' => [
                        'user_id' => $creator->user_id,
                        'username' => $creator->username,
                        'avt_image' => $creator->avt_image,
                        'full_name' => $creator->full_name,
                        'shop_name' => $creator->shop_name,
                        'shop_username' => $creator->shop_username,
                        'shop_avt' => $creator->shop_avt,
                        'shop_background' => $creator->shop_background,
                        'shop_introduce' => $creator->shop_introduce,
                    ],
                    'product_brand_id' => $product->product_brand_id,
                    'product_category_id' => $product->product_category_id,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'discount_id' => $product->discount_id,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'deleted_at' => $product->deleted_at,
                    'image_urls' => $imageUrls,
                    'sizes' => $sizes,
                    'colors' => $colors,
                    'reviews' => $formattedReviews,
                    'average_rating' => $averageRating,
                    'average_rating_by_creator' => $averageRatingByCreator,
                    'number_of_products_by_creator' => $numberOfProducts,
                    'total_reviews' => $totalReviews,
                    'review_counts' => $reviewCounts->toArray(),
                ],
            ];

            return response()->json($arr, 200);
        } catch (ModelNotFoundException $e) {
            $arr = [
                'status' => false,
                'message' => 'Không có sản phẩm này',
                'data' => null,
            ];

            return response()->json($arr, 404);
        }
    }




    public function update(Request $request, string $product)
    {
        $input = $request->all();

        $product = Product::find($product);

        if (!$product) {
            $arr = [
                'status' => false,
                'message' => 'Sản phẩm không tồn tại',
                'data' => null
            ];
            return response()->json($arr, 404);
        }

        $product->update($input);


        $arr = [
            'status' => true,
            'message' => 'Sản phẩm cập nhật thành công',
            'data' => $product
        ];

        return response()->json($arr, 200);
    }

    public function destroy(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            $arr = [
                'status' => true,
                'message' => 'Sản phẩm đã được xóa thành công',
                'data' => null
            ];

            return response()->json($arr, 200);
        } catch (ModelNotFoundException $e) {
            $arr = [
                'success' => false,
                'message' => 'Sản phẩm không tồn tại',
                'data' => null
            ];

            return response()->json($arr, 404);
        }
    }

    public function getLatestProductsInCategory(Request $request, $categoryId) // Lấy ra 6 sản phẩm mới nhất trong 1 category bất kì
    {
        $latestProducts = Product::where('product_category_id', $categoryId)
            ->latest('created_at')
            ->limit(6)
            ->get();

        return response()->json(['latestProducts' => $latestProducts]);
    }

    public function getBestSellingProductsInCategory(Request $request, $categoryId)
    {
        try {
            $topProducts = Product::select(
                'product.product_id',
                'product.name',
                'product.description',
                'product.price',
                'product.stock',
                DB::raw('SUM(order_items.quantity) as total_sales')
            )
                ->join('order_items', 'product.product_id', '=', 'order_items.product_id')
                ->where('product.product_category_id', $categoryId)
                ->groupBy('product.product_id', 'product.name', 'product.description', 'product.price', 'product.stock')
                ->orderByDesc('total_sales')
                ->take(6)
                ->get();

            foreach ($topProducts as &$product) {
                $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
                $product->images = $productImages;
            }

            return response()->json([
                'status' => 200,
                'message' => 'Top 6 sản phẩm của 1 danh mục bán chạy nhất',
                'data' => $topProducts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBestSellingUserProducts(Request $request, $userId)
    {
        try {
            $topProducts = Product::select(
                'product.product_id',
                'product.name',
                'product.description',
                'product.price',
                'product.stock',
                DB::raw('SUM(order_items.quantity) as total_sales'),
                DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
            )
                ->leftJoin('order_items', 'product.product_id', '=', 'order_items.product_id')
                ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                ->where('product.created_by_user_id', $userId)
                ->groupBy(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock'
                )
                ->orderByDesc('total_sales')
                ->take(8)
                ->get();

            foreach ($topProducts as &$product) {
                $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
                $product->images = $productImages;
            }

            return response()->json([
                'status' => 200,
                'message' => 'Top 8 sản phẩm của user id ' . $userId . ' bán chạy nhất',
                'data' => $topProducts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listProductWithCategory(Request $request, $categoryId)
    {
        try {
            $products = Product::select(
                'product.*',
                'product_review.rating',
                DB::raw('SUM(order_items.quantity) as total_sales')
            )
                ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                ->leftJoin('order_items', 'product.product_id', '=', 'order_items.product_id')
                ->leftJoin('order', 'order_items.order_id', '=', 'order.order_id')
                ->where('product.product_category_id', $categoryId)
                ->groupBy(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    'product.created_by_user_id',
                    'product.product_brand_id',
                    'product.product_category_id',
                    'product.discount_id',
                    'product.created_at',
                    'product.updated_at',
                    'product.deleted_at',
                    'product_review.rating',
                )
                ->orderByDesc('product_review.rating')
                ->orderByDesc('total_sales')
                ->get();

            foreach ($products as &$product) {
                $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
                $product->images = $productImages;
            }

            return response()->json([
                'status' => 200,
                'message' => 'Danh sách sản phẩm theo danh mục sắp xếp theo độ đánh giá và lượt bán',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function listProductWithBrand(Request $request, $brandId)
    {
        try {
            $products = Product::select(
                'product.*',
                'product_review.rating',
                DB::raw('SUM(order_items.quantity) as total_sales')
            )
                ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                ->leftJoin('order_items', 'product.product_id', '=', 'order_items.product_id')
                ->leftJoin('order', 'order_items.order_id', '=', 'order.order_id')
                ->where('product.product_brand_id', $brandId)
                ->where('order.order_status_id', 3)
                ->groupBy('product.product_id')
                ->groupBy(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    'product.color_id',
                    'product.size_id',
                    'product.created_by_user_id',
                    'product.product_brand_id',
                    'product.product_category_id',
                    'product.discount_id',
                    'product.created_at',
                    'product.updated_at',
                    'product.deleted_at',
                    'product_review.rating'
                )
                ->orderByDesc('product_review.rating')
                ->orderByDesc('total_sales')
                ->get();
            foreach ($products as &$product) {
                $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
                $product->images = $productImages;
            }
            return response()->json([
                'status' => 200,
                'message' => 'Danh sách sản phẩm theo danh mục sắp xếp theo độ đánh giá và lượt bán',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Gợi ý sản phẩm
    public function recommendBaseOnSearch(Request $request, $user_id)
    {
        // Lấy danh sách từ khóa tìm kiếm gần đây của người dùng
        $recentSearches = search_history::where('user_id', $user_id)
            ->orderByDesc('created_at')
            ->pluck('keyword')
            ->take(5)
            ->toArray();

        // Kiểm tra nếu không có từ khóa tìm kiếm gần đây
        if (empty($recentSearches)) {
            $categoryId = $request->has('category_id') ? $request->input('category_id') : 1;
            return $this->getBestSellingProductsInCategory($request, $categoryId);
        }

        $relatedProducts = collect();

        // Lấy sản phẩm dựa trên từ khóa tìm kiếm gần đây
        foreach ($recentSearches as $search) {
            $products = Product::where('name', 'like', "%$search%")->take(2)->get();
            $relatedProducts = $relatedProducts->merge($products);
        }

        // Lấy ảnh của sản phẩm từ bảng product_image
        $relatedProductsWithImages = $relatedProducts->map(function ($product) {
            $images = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
            $product->images = $images;
            return $product;
        });

        if ($relatedProductsWithImages->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Không có sản phẩm nào được tìm thấy dựa trên từ khóa tìm kiếm gần đây',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách sản phẩm gợi ý dựa trên từ khóa tìm kiếm gần đây',
            'data' => $relatedProductsWithImages,
        ]);
    }

    public function getRandomCategories()
    {
        try {
            // Specify the unique category IDs you want to fetch
            $categoryIds = [1, 27];

            // Get the specified categories
            $specificCategories = DB::table('product_category')
                ->whereIn('product_category_id', $categoryIds)
                ->get(['product_category_id', 'product_category_name']);

            $result = [];

            foreach ($specificCategories as $category) {
                // Lấy thông tin sản phẩm bán chạy nhất của từng danh mục
                $topProducts = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('SUM(order_items.quantity) as total_sales')
                )
                    ->join('order_items', 'product.product_id', '=', 'order_items.product_id')
                    ->where('product.product_category_id', $category->product_category_id)
                    ->groupBy('product.product_id', 'product.name', 'product.description', 'product.price', 'product.stock')
                    ->orderByDesc('total_sales')
                    ->take(4)
                    ->get();

                foreach ($topProducts as &$product) {
                    // Lấy danh sách ảnh của sản phẩm
                    $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
                    $product->images = $productImages;
                }

                $result[] = [
                    'category_id' => $category->product_category_id,
                    'product_category_name' => $category->product_category_name,
                    'top_products' => $topProducts,
                ];
            }

            return response()->json([
                'status' => 200,
                'message' => 'Danh sách 4 danh mục và top 4 sản phẩm bán chạy nhất của từng danh mục',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Internal Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function filterByCategoriesAndBrands(Request $request)
    {
        $categoryIds = $request->input('category_ids');
        $brandIds = $request->input('brand_ids');
        $userId = $request->input('user_id');
        $sortBy = $request->input('type_sort');

        $query = Product::query()->select(
            'product.product_id',
            'product.name',
            'product.description',
            'product.price',
            'product.stock',
            DB::raw('SUM(order_items.quantity) as total_sales'),
            DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
        )
            ->leftJoin('order_items', 'product.product_id', '=', 'order_items.product_id')
            ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
            ->where('product.created_by_user_id', $userId)
            ->groupBy(
                'product.product_id',
                'product.name',
                'product.description',
                'product.price',
                'product.stock'
            );

        if (!empty($categoryIds)) {
            $query->whereIn('product.product_id', function ($subquery) use ($categoryIds, $userId) {
                $subquery->select('product.product_id')
                    ->from('product')
                    ->join('product_category', 'product.product_category_id', '=', 'product_category.product_category_id')
                    ->where('product.created_by_user_id', '=', $userId)
                    ->whereIn('product_category.product_category_id', $categoryIds);
            });
        }

        if (!empty($brandIds)) {
            $query->whereIn('product.product_id', function ($subquery) use ($brandIds, $userId) {
                $subquery->select('product.product_id')
                    ->from('product')
                    ->join('product_brand', 'product.product_brand_id', '=', 'product_brand.product_brand_id')
                    ->where('product.created_by_user_id', '=', $userId)
                    ->whereIn('product_brand.product_brand_id', $brandIds);
            });
        }

        $perPage = 9;

        switch ($sortBy) {
            case 'newest':
                $query->orderBy('product.created_at', 'desc');
                break;

            case 'price_high_to_low':
                $query->orderBy('product.price', 'desc');
                break;

            case 'price_low_to_high':
                $query->orderBy('product.price', 'asc');
                break;
        }

        $products = $query->paginate($perPage);

        foreach ($products as &$product) {
            $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
            $product->images = $productImages;
        }

        if ($products->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Không có sản phẩm nào được tìm thấy dựa trên các điều kiện lọc',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách sản phẩm được lọc theo category và brand',
            'data' => $products,
        ], 200);
    }


    public function sortProducts($sortBy)
    {
        $perPage = 8;

        switch ($sortBy) {
            case 'rating':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('average_rating', 'desc')
                    ->paginate($perPage);
                break;

            case 'newest':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('product.created_at', 'desc')
                    ->paginate($perPage);
                break;

            case 'price_high_to_low':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('price', 'desc')
                    ->paginate($perPage);
                break;

            case 'price_low_to_high':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('price', 'asc')
                    ->paginate($perPage);
                break;

            default:
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->paginate($perPage);
                break;
        }

        foreach ($products as &$product) {
            $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
            $product->images = $productImages;
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách sản phẩm được lọc theo ' . $sortBy,
            'data' => $products,
        ], 200);
    }


    public function sortUserProducts($sortBy, $userId)
    {
        $perPage = 8;

        switch ($sortBy) {
            case 'sell':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(SUM(order_items.quantity), 0) as total_sell'),
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('order_items', 'product.product_id', '=', 'order_items.product_id')
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->where('product.created_by_user_id', $userId)
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('total_sell', 'desc')
                    ->paginate($perPage);
                break;

            case 'newest':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->where('product.created_by_user_id', $userId)
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('product.created_at', 'desc')
                    ->paginate($perPage);
                break;

            case 'price_high_to_low':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->where('product.created_by_user_id', $userId)
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('price', 'desc')
                    ->paginate($perPage);
                break;

            case 'price_low_to_high':
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->where('product.created_by_user_id', $userId)
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->orderBy('price', 'asc')
                    ->paginate($perPage);
                break;

            default:
                $products = Product::select(
                    'product.product_id',
                    'product.name',
                    'product.description',
                    'product.price',
                    'product.stock',
                    DB::raw('IFNULL(AVG(product_review.rating), 0) as average_rating')
                )
                    ->leftJoin('product_review', 'product.product_id', '=', 'product_review.product_id')
                    ->where('product.created_by_user_id', $userId)
                    ->groupBy(
                        'product.product_id',
                        'product.name',
                        'product.description',
                        'product.price',
                        'product.stock'
                    )
                    ->paginate($perPage);
                break;
        }

        foreach ($products as &$product) {
            $productImages = ProductImage::where('product_id', $product->product_id)->pluck('image_url');
            $product->images = $productImages;
        }

        return response()->json([
            'status' => true,
            'message' => 'Danh sách sản phẩm của user_id ' . $userId . ' được lọc theo ' . $sortBy,
            'data' => $products,
        ], 200);
    }
    public function createByShop(string $userId)
    {
        try {

            $products = Product::where('created_by_user_id', $userId)
                ->join('product_category', 'product.product_category_id', '=', 'product_category.product_category_id')
                ->join('product_brand', 'product.product_brand_id', '=', 'product_brand.product_brand_id')
                ->select('product.*', 'product_category.product_category_name', 'product_brand.product_brand_name')
                ->paginate(7);

            if ($products->isEmpty()) {
                $arr = [
                    'status' => false,
                    'message' => 'Người dùng chưa tạo sản phẩm nào',
                    'data' => null,
                ];

                return response()->json($arr, 404);
            }

            $arr = [
                'status' => true,
                'message' => 'Thông tin sản phẩm của người dùng',
                'data' => $products
            ];

            return response()->json($arr, 200);
        } catch (ModelNotFoundException $e) {
            $arr = [
                'status' => false,
                'message' => 'Không tìm thấy người dùng',
                'data' => null,
            ];

            return response()->json($arr, 404);
        }
    }

    public function searchProduct(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Get the name from the request
        $name = $request->input('name');

        // Query the database for products matching the name
        $products = Product::where('name', 'like', '%' . $name . '%')->paginate(6);

        // Check if any products were found
        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found for the given name'], 404);
        }

        // Return the products with pagination
        return response()->json($products, 200);
    }
}