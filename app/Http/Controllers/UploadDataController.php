<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use App\Models\Category;
use App\Models\Product;
use App\Jobs\SyncCategoryToShopifyJob;
use App\Jobs\SyncProductToShopifyJob;
use App\Jobs\SyncAllCategoriesToShopifyJob;
use App\Jobs\SyncAllProductsToShopifyJob;
use App\Jobs\SyncProductsByCategoryJob;
use Illuminate\Support\Facades\Log;

class UploadDataController extends Controller
{
    private $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    /**
     * Kiểm tra kết nối Shopify
     */
    public function checkShopifyConnection()
    {
        try {
            $response = $this->shopifyService->checkConnection();

            if ($response['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Kết nối Shopify thành công',
                    'shop' => $response['data']['shop'] ?? null
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không thể kết nối đến Shopify',
                'error' => $response['error'] ?? 'Lỗi không xác định'
            ], 500);

        } catch (\Throwable $e) {
            \Log::error('Lỗi khi kiểm tra kết nối Shopify: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi không mong muốn',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ danh mục lên Shopify (Dispatch Job)
     */
    public function syncCategoryToShopify(Category $category)
    {
        try {
            // Dispatch job để đồng bộ category
            SyncCategoryToShopifyJob::dispatch($category)
                ->onQueue('shopify-sync');

            Log::info("Đã dispatch job đồng bộ category '{$category->title}'");

            return response()->json([
                'success' => true,
                'message' => "Đã gửi yêu cầu đồng bộ danh mục '{$category->title}' lên Shopify. Job đang chạy ngầm.",
                'category' => [
                    'title' => $category->title,
                    'level' => $category->level,
                    'code' => $category->code
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi dispatch job đồng bộ danh mục: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi gửi yêu cầu đồng bộ danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ sản phẩm lên Shopify (Dispatch Job)
     */
    public function syncProductToShopify(Product $product)
    {
        try {
            // Dispatch job để đồng bộ product
            SyncProductToShopifyJob::dispatch($product)
                ->onQueue('shopify-sync');

            Log::info("Đã dispatch job đồng bộ product '{$product->title}'");

            return response()->json([
                'success' => true,
                'message' => "Đã gửi yêu cầu đồng bộ sản phẩm '{$product->title}' lên Shopify. Job đang chạy ngầm.",
                'product' => [
                    'title' => $product->title,
                    'code' => $product->code,
                    'category' => $product->category ? $product->category->title : 'N/A'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi dispatch job đồng bộ sản phẩm: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi gửi yêu cầu đồng bộ sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ tất cả danh mục lên Shopify (Dispatch Job)
     */
    public function syncAllCategoriesToShopify()
    {
        try {
            $totalCategories = Category::count();

            // Dispatch job để đồng bộ tất cả categories
            SyncAllCategoriesToShopifyJob::dispatch()
                ->onQueue('shopify-sync');

            Log::info("Đã dispatch job đồng bộ tất cả categories ({$totalCategories} categories)");

            return response()->json([
                'success' => true,
                'message' => "Đã gửi yêu cầu đồng bộ tất cả danh mục lên Shopify. Job đang chạy ngầm.",
                'summary' => [
                    'total_categories' => $totalCategories,
                    'status' => 'Job dispatched',
                    'queue' => 'shopify-sync'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi dispatch job đồng bộ tất cả danh mục: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi gửi yêu cầu đồng bộ tất cả danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ tất cả sản phẩm lên Shopify (Dispatch Job)
     */
    public function syncAllProductsToShopify()
    {
        try {
            $totalProducts = Product::count();

            // Dispatch job để đồng bộ tất cả products
            SyncAllProductsToShopifyJob::dispatch()
                ->onQueue('shopify-sync');

            Log::info("Đã dispatch job đồng bộ tất cả products ({$totalProducts} products)");

            return response()->json([
                'success' => true,
                'message' => "Đã gửi yêu cầu đồng bộ tất cả sản phẩm lên Shopify. Job đang chạy ngầm.",
                'summary' => [
                    'total_products' => $totalProducts,
                    'status' => 'Job dispatched',
                    'queue' => 'shopify-sync'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi dispatch job đồng bộ tất cả sản phẩm: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi gửi yêu cầu đồng bộ tất cả sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ sản phẩm theo danh mục (Dispatch Job)
     */
    public function syncProductsByCategory(Category $category)
    {
        try {
            $totalProducts = Product::where('category_code', $category->code)->count();

            if ($totalProducts === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Không tìm thấy sản phẩm nào trong danh mục '{$category->title}'"
                ], 404);
            }

            // Dispatch job để đồng bộ products theo category
            SyncProductsByCategoryJob::dispatch($category)
                ->onQueue('shopify-sync');

            Log::info("Đã dispatch job đồng bộ products cho category '{$category->title}' ({$totalProducts} products)");

            return response()->json([
                'success' => true,
                'message' => "Đã gửi yêu cầu đồng bộ sản phẩm danh mục '{$category->title}' lên Shopify. Job đang chạy ngầm.",
                'category' => [
                    'title' => $category->title,
                    'level' => $category->level,
                    'code' => $category->code
                ],
                'summary' => [
                    'total_products' => $totalProducts,
                    'status' => 'Job dispatched',
                    'queue' => 'shopify-sync'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Lỗi dispatch job đồng bộ sản phẩm danh mục '{$category->title}': " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Lỗi gửi yêu cầu đồng bộ sản phẩm danh mục '{$category->title}'",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dispatch job xóa tất cả products trên Shopify (chạy ngầm)
     */
    public function deleteAllProducts()
    {
        try {
            \Log::info("Dispatch job xóa tất cả products trên Shopify");
            
            // Dispatch job vào queue
            \App\Jobs\DeleteAllProductsJob::dispatch()->onQueue('shopify-sync');
            
            return response()->json([
                'success' => true,
                'message' => 'Job xóa tất cả products đã được dispatch. Kiểm tra logs để theo dõi tiến trình.',
                'note' => 'Chạy: php artisan queue:work --queue=shopify-sync'
            ]);
            
        } catch (\Throwable $e) {
            \Log::error("Lỗi dispatch job xóa products: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chuẩn bị dữ liệu danh mục cho Shopify
     */
    private function prepareCategoryData(Category $category)
    {
        $description = "<h2>{$category->title}</h2>";
        
        // Thêm thông tin level
        $description .= "<p><strong>Level:</strong> {$category->level}</p>";
        
        // Thêm thông tin parent nếu có
        if ($category->parent) {
            $description .= "<p><strong>Danh mục cha:</strong> {$category->parent->title}</p>";
        }
        
        // Thêm số lượng sản phẩm
        $productsCount = $category->products->count();
        $description .= "<p><strong>Số sản phẩm:</strong> {$productsCount}</p>";

        return [
            'title' => $category->title,
            'handle' => $category->slug,
            'descriptionHtml' => $description,
            'seo' => [
                'title' => $category->title,
                'description' => "Danh mục {$category->title} - Level {$category->level}"
            ]
        ];
    }

    /**
     * Chuẩn bị dữ liệu sản phẩm cho Shopify
     */
    private function prepareProductData(Product $product)
    {
        $productData = [
            'title' => $product->title,
            'handle' => $product->slug,
            'descriptionHtml' => $this->formatProductDescription($product),
            'productType' => $product->category ? $product->category->title : 'General',
            'vendor' => 'Crystal Store',
            'tags' => $this->formatProductTags($product),
            'seo' => [
                'title' => $product->title,
                'description' => $product->title
            ]
        ];

        // Thêm hình ảnh
        $images = [];
        
        // Hình ảnh chính
        if ($product->image) {
            $images[] = [
                'src' => url($product->image),
                'altText' => $product->title,
                'position' => 1
            ];
        }

        // Gallery từ ProductDetail
        if ($product->productDetail && $product->productDetail->gallery_local) {
            $galleryImages = json_decode($product->productDetail->gallery_local, true);
            if (is_array($galleryImages)) {
                foreach ($galleryImages as $index => $imagePath) {
                    $images[] = [
                        'src' => url($imagePath),
                        'altText' => $product->title . ' - Hình ' . ($index + 2),
                        'position' => $index + 2
                    ];
                }
            }
        }

        if (!empty($images)) {
            $productData['images'] = $images;
        }

        // Thêm variants
        $variants = [];
        if ($product->variants->count() > 0) {
            foreach ($product->variants as $variant) {
                $variantData = [
                    'price' => $variant->price ?? $product->price,
                    'sku' => $variant->art_no ?? $product->code,
                    'inventoryQuantity' => 0,
                    'weight' => 0,
                    'weightUnit' => 'KILOGRAMS'
                ];

                // Thêm tên variant nếu có
                if ($variant->name) {
                    $variantData['title'] = $variant->name;
                }

                // Thêm hình ảnh variant nếu có
                if ($variant->variant_image) {
                    $variantData['image'] = [
                        'src' => url($variant->variant_image),
                        'altText' => $variant->name ?? $product->title
                    ];
                }

                // Xử lý stock status
                if ($variant->stock_status) {
                    switch (strtolower($variant->stock_status)) {
                        case 'skladem':
                            $variantData['inventoryQuantity'] = 10; // Có sẵn
                            break;
                        case 'akce':
                            $variantData['inventoryQuantity'] = 5; // Khuyến mãi
                            break;
                        default:
                            $variantData['inventoryQuantity'] = 0;
                    }
                }

                $variants[] = $variantData;
            }
        } else {
            // Tạo variant mặc định
            $variants[] = [
                'price' => $product->price,
                'sku' => $product->code,
                'inventoryQuantity' => 0,
                'weight' => 0,
                'weightUnit' => 'KILOGRAMS'
            ];
        }

        $productData['variants'] = $variants;

        return $productData;
    }

    /**
     * Format mô tả sản phẩm
     */
    private function formatProductDescription(Product $product)
    {
        $description = "<h2>{$product->title}</h2>";
        
        // Mô tả dài từ ProductDetail
        if ($product->productDetail && $product->productDetail->long_description) {
            $description .= "<div class='product-description'>{$product->productDetail->long_description}</div>";
        }

        // Đặc điểm từ Product
        if ($product->indicators) {
            $indicators = json_decode($product->indicators, true);
            if (is_array($indicators)) {
                $description .= "<h3>Main Features</h3><ul>";
                foreach ($indicators as $indicator) {
                    $description .= "<li>" . htmlspecialchars($indicator) . "</li>";
                }
                $description .= "</ul>";
            } else {
                $description .= "<h3>Main Features</h3><p>{$product->indicators}</p>";
            }
        }

        // Đặc điểm chi tiết từ ProductDetail
        if ($product->productDetail && $product->productDetail->detail_indicators) {
            $indicators = json_decode($product->productDetail->detail_indicators, true);
            if (is_array($indicators)) {
                $description .= "<h3>Detailed Features</h3><ul>";
                foreach ($indicators as $indicator) {
                    $description .= "<li>" . htmlspecialchars($indicator) . "</li>";
                }
                $description .= "</ul>";
            } else {
                $description .= "<h3>Detailed Features</h3><p>{$product->productDetail->detail_indicators}</p>";
            }
        }

        // Thông số kỹ thuật
        if ($product->productDetail && $product->productDetail->specs) {
            $specs = json_decode($product->productDetail->specs, true);
            if (is_array($specs)) {
                $description .= "<h3>Technical Specifications</h3><table class='specs-table'>";
                foreach ($specs as $key => $value) {
                    $description .= "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
                }
                $description .= "</table>";
            } else {
                $description .= "<h3>Technical Specifications</h3><div>{$product->productDetail->specs}</div>";
            }
        }

        // Tính năng chính
        if ($product->productDetail && $product->productDetail->key_features) {
            $features = json_decode($product->productDetail->key_features, true);
            if (is_array($features)) {
                $description .= "<h3>Key Features</h3><ul>";
                foreach ($features as $feature) {
                    $description .= "<li>" . htmlspecialchars($feature) . "</li>";
                }
                $description .= "</ul>";
            } else {
                $description .= "<h3>Key Features</h3><div>{$product->productDetail->key_features}</div>";
            }
        }

        // Meta description
        if ($product->productDetail && $product->productDetail->meta_description) {
            $description .= "<div class='meta-info'><p><strong>Thông tin bổ sung:</strong> {$product->productDetail->meta_description}</p></div>";
        }

        return $description;
    }

    /**
     * Format tags cho sản phẩm
     */
    private function formatProductTags(Product $product)
    {
        $tags = [];

        // Category
        if ($product->category) {
            $tags[] = $product->category->title;
        }

        // Discount
        if ($product->discount) {
            $tags[] = 'Khuyến mãi';
        }

        // Currency
        if ($product->currency) {
            $tags[] = $product->currency;
        }

        // Stock status từ variants
        if ($product->variants->count() > 0) {
            foreach ($product->variants as $variant) {
                if ($variant->stock_status) {
                    $tags[] = $variant->stock_status;
                }
            }
        }

        // Loại bỏ duplicates và empty values
        $tags = array_filter(array_unique($tags));

        return implode(', ', $tags);
    }
}
