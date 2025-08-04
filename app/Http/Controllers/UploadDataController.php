<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use App\Models\Category;
use App\Models\Product;
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
     * Đồng bộ danh mục lên Shopify
     */
    public function syncCategoryToShopify(Category $category)
    {
        try {
            // Kiểm tra xem danh mục đã tồn tại trên Shopify chưa
            $existingCollection = $this->shopifyService->findCollectionByHandle($category->slug);
            
            $collectionData = $this->prepareCategoryData($category);
            
            if ($existingCollection['success'] && isset($existingCollection['data']['collectionByHandle'])) {
                // Cập nhật collection đã tồn tại
                $result = $this->shopifyService->updateCollection(
                    $existingCollection['data']['collectionByHandle']['id'], 
                    $collectionData
                );
                $action = 'cập nhật';
            } else {
                // Tạo collection mới
                $result = $this->shopifyService->createCollection($collectionData);
                $action = 'tạo mới';
            }

            if ($result['success']) {
                Log::info("Đã {$action} danh mục '{$category->title}' trên Shopify");
                return response()->json([
                    'success' => true,
                    'message' => "Đã {$action} danh mục '{$category->title}' trên Shopify thành công",
                    'data' => $result['data']
                ]);
            } else {
                Log::error("Lỗi {$action} danh mục '{$category->title}' trên Shopify: " . $result['error']);
                return response()->json([
                    'success' => false,
                    'message' => "Lỗi {$action} danh mục trên Shopify",
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Lỗi đồng bộ danh mục lên Shopify: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đồng bộ danh mục lên Shopify',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ sản phẩm lên Shopify
     */
    public function syncProductToShopify(Product $product)
    {
        try {
            // Kiểm tra xem sản phẩm đã tồn tại trên Shopify chưa
            $existingProduct = $this->shopifyService->findProductByHandle($product->slug);
            
            $productData = $this->prepareProductData($product);
            
            if ($existingProduct['success'] && isset($existingProduct['data']['productByHandle'])) {
                // Cập nhật product đã tồn tại
                $result = $this->shopifyService->updateProduct(
                    $existingProduct['data']['productByHandle']['id'], 
                    $productData
                );
                $action = 'cập nhật';
            } else {
                // Tạo product mới
                $result = $this->shopifyService->createProduct($productData);
                $action = 'tạo mới';
            }

            if ($result['success']) {
                Log::info("Đã {$action} sản phẩm '{$product->title}' trên Shopify");
                return response()->json([
                    'success' => true,
                    'message' => "Đã {$action} sản phẩm '{$product->title}' trên Shopify thành công",
                    'data' => $result['data']
                ]);
            } else {
                Log::error("Lỗi {$action} sản phẩm '{$product->title}' trên Shopify: " . $result['error']);
                return response()->json([
                    'success' => false,
                    'message' => "Lỗi {$action} sản phẩm trên Shopify",
                    'error' => $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Lỗi đồng bộ sản phẩm lên Shopify: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đồng bộ sản phẩm lên Shopify',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ tất cả danh mục lên Shopify
     */
    public function syncAllCategoriesToShopify()
    {
        try {
            $categories = Category::all();
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($categories as $category) {
                $response = $this->syncCategoryToShopify($category);
                $responseData = json_decode($response->getContent(), true);
                
                if ($responseData['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
                
                $results[] = [
                    'category' => $category->title,
                    'success' => $responseData['success'],
                    'message' => $responseData['message']
                ];
            }

            return response()->json([
                'success' => true,
                'message' => "Đồng bộ hoàn tất: {$successCount} thành công, {$errorCount} lỗi",
                'results' => $results,
                'summary' => [
                    'total' => count($categories),
                    'success' => $successCount,
                    'error' => $errorCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi đồng bộ tất cả danh mục: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đồng bộ tất cả danh mục',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Đồng bộ tất cả sản phẩm lên Shopify
     */
    public function syncAllProductsToShopify()
    {
        try {
            $products = Product::with(['category', 'variants', 'productDetail'])->get();
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($products as $product) {
                $response = $this->syncProductToShopify($product);
                $responseData = json_decode($response->getContent(), true);
                
                if ($responseData['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
                
                $results[] = [
                    'product' => $product->title,
                    'success' => $responseData['success'],
                    'message' => $responseData['message']
                ];
            }

            return response()->json([
                'success' => true,
                'message' => "Đồng bộ hoàn tất: {$successCount} thành công, {$errorCount} lỗi",
                'results' => $results,
                'summary' => [
                    'total' => count($products),
                    'success' => $successCount,
                    'error' => $errorCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Lỗi đồng bộ tất cả sản phẩm: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đồng bộ tất cả sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chuẩn bị dữ liệu danh mục cho Shopify
     */
    private function prepareCategoryData(Category $category)
    {
        return [
            'title' => $category->title,
            'handle' => $category->slug,
            'descriptionHtml' => "<p>{$category->title}</p>",
            'seo' => [
                'title' => $category->title,
                'description' => $category->title
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

        // Thêm hình ảnh nếu có
        if ($product->image) {
            $productData['images'] = [
                [
                    'src' => url($product->image),
                    'altText' => $product->title
                ]
            ];
        }

        // Thêm variants
        $variants = [];
        if ($product->variants->count() > 0) {
            foreach ($product->variants as $variant) {
                $variants[] = [
                    'price' => $variant->price ?? $product->price,
                    'compareAtPrice' => $variant->compare_at_price ?? null,
                    'sku' => $variant->sku ?? $product->code,
                    'inventoryQuantity' => $variant->inventory_quantity ?? 0,
                    'weight' => $variant->weight ?? 0,
                    'weightUnit' => 'KILOGRAMS'
                ];
            }
        } else {
            // Tạo variant mặc định
            $variants[] = [
                'price' => $product->price,
                'compareAtPrice' => $product->discount ? $product->price * (1 + $product->discount / 100) : null,
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
        
        if ($product->productDetail) {
            $description .= "<p>{$product->productDetail->description}</p>";
        }

        if ($product->indicators) {
            $description .= "<p><strong>Đặc điểm:</strong> {$product->indicators}</p>";
        }

        return $description;
    }

    /**
     * Format tags cho sản phẩm
     */
    private function formatProductTags(Product $product)
    {
        $tags = [];

        if ($product->category) {
            $tags[] = $product->category->title;
        }

        if ($product->discount) {
            $tags[] = 'Khuyến mãi';
        }

        return implode(', ', $tags);
    }
}
