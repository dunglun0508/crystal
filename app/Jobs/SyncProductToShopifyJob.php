<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Log;

class SyncProductToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $product;
    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopifyService)
    {
        try {
            \Log::info("Sync product: {$this->product->title}");
            
            $productData = $this->prepareProductData($this->product);
            $existingProductResult = $shopifyService->findProductByHandle($productData['handle']);
            
            \Log::info("Product search result: " . json_encode($existingProductResult));
            
            $existingProduct = null;
            $productId = null;
            
            // Kiểm tra kết quả tìm product
            if ($existingProductResult && ($existingProductResult['success'] ?? false)) {
                $foundProductData = $existingProductResult['data']['productByHandle'] ?? null;
                if ($foundProductData && isset($foundProductData['id'])) {
                    $existingProduct = $foundProductData;
                    $productId = $foundProductData['id'];
                    \Log::info("Found existing product: {$foundProductData['title']} (ID: {$foundProductData['id']})");
                } else {
                    \Log::info("Product not found in Shopify (handle: {$productData['handle']})");
                }
            } else {
                \Log::warning("Failed to check product existence: " . json_encode($existingProductResult));
            }
            
            // Tạo hoặc cập nhật product
            if (!$existingProduct) {
                \Log::info("Create new product: {$this->product->title}");
                
                // Tạo product với variants
                $createResult = $shopifyService->createProduct($productData);
                
                if ($createResult && isset($createResult['id'])) {
                    $productId = $createResult['id'];
                    
                    // Thêm images sau khi tạo product
                    if (!empty($createResult['images'])) {
                        \Log::info("Adding " . count($createResult['images']) . " images to product");
                        $shopifyService->addProductImages($productId, $createResult['images']);
                    }
                    
                    // Thêm tags sau khi tạo product
                    if (!empty($createResult['tags'])) {
                        \Log::info("Adding tags to product: " . $createResult['tags']);
                        $shopifyService->addProductTags($productId, $createResult['tags']);
                    }
                    
                    // Thêm product vào collection sau khi tạo product
                    if (!empty($createResult['collectionId'])) {
                        \Log::info("Adding product to collection: " . $createResult['collectionId']);
                        $shopifyService->addProductToCollection($productId, $createResult['collectionId']);
                    }
                    
                    // Inventory đã được xử lý trong createProductVariantsWithInventory
                    \Log::info("Product synced successfully: {$this->product->title}");
                } else {
                    throw new \Exception("Failed to create product: {$this->product->title}");
                }
            } else {
                // Cập nhật product hiện có
                \Log::info("Update existing product: {$this->product->title}");
                $result = $shopifyService->updateProduct($productId, $productData);
                
                if (!($result['success'] ?? false)) {
                    throw new \Exception("Failed to update product: " . json_encode($result));
                }
                
                \Log::info("Product updated successfully: {$this->product->title}");
            }
            
        } catch (\Throwable $e) {
            \Log::error("Sync product failed {$this->product->title}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Chuẩn bị dữ liệu sản phẩm cho Shopify
     */
    private function prepareProductData(Product $product)
    {
        $productData = [
            'title' => $product->title,
            // Shopify handle không chấp nhận dấu '/'. Chuyển slug thành dạng hợp lệ
            'handle' => trim(preg_replace('/[^a-z0-9-]/', '-', strtolower(str_replace('/', '-', $product->slug))), '-'),
            'descriptionHtml' => $this->formatProductDescription($product),
            'productType' => $product->category ? $product->category->title : 'General',
            'vendor' => 'Crystal Store',
            'tags' => $this->formatProductTags($product),
            'seo' => [
                'title' => $product->title,
                'description' => $product->title
            ]
        ];

        // Thêm collectionId nếu có category
        if ($product->category) {
            // Tạo collection handle theo format Shopify
            $collectionHandle = $this->generateShopifyCollectionHandle($product->category);
            Log::info("Looking for collection with handle: " . $collectionHandle);
            
            $collection = app(ShopifyService::class)->findCollectionByHandle($collectionHandle);
            
            Log::info("Collection search result: " . json_encode($collection));
            
            if ($collection && ($collection['success'] ?? false) && isset($collection['data']['collectionByHandle']['id'])) {
                $productData['collectionId'] = $collection['data']['collectionByHandle']['id'];
                Log::info("Found collection ID: " . $productData['collectionId']);
            } else {
                Log::warning("Collection not found for handle: " . $collectionHandle);
            }
        }

        // Thêm hình ảnh
        $images = [];
        
        // Hình ảnh chính
        if ($product->image) {
            $mainUrl = $this->toPublicUrl($product->image);
            if ($mainUrl) {
                $images[] = [
                    'src' => $mainUrl,
                    'altText' => $product->title,
                    'position' => 1
                ];
            }
        }
        
        // Gallery từ ProductDetail
        if ($product->productDetail && $product->productDetail->gallery_local) {
            $galleryImages = json_decode($product->productDetail->gallery_local, true);
            if (is_array($galleryImages)) {
                foreach ($galleryImages as $index => $imagePath) {
                    $url = $this->toPublicUrl($imagePath);
                    if (!$url) {
                        continue;
                    }
                    $images[] = [
                        'src' => $url,
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

                // Thêm tên variant nếu có - sử dụng name từ bảng product_variants
                if ($variant->name) {
                    $variantData['title'] = $variant->name;
                } else {
                    // Nếu không có name, sử dụng art_no hoặc code
                    $variantData['title'] = $variant->art_no ?? $product->code;
                }

                // Thêm hình ảnh variant nếu có
                if ($variant->variant_image) {
                    $variantUrl = $this->toPublicUrl($variant->variant_image);
                    if ($variantUrl) {
                        $variantData['image'] = [
                            'src' => $variantUrl,
                            'altText' => $variant->name ?? $product->title
                        ];
                    }
                }

                // Xử lý stock status từ bảng product_variants
                if ($variant->stock_status) {
                    switch (strtolower($variant->stock_status)) {
                        case 'skladem':
                            $variantData['inventoryQuantity'] = 50; // Có sẵn - tăng số lượng
                            break;
                        case 'akce':
                            $variantData['inventoryQuantity'] = 25; // Khuyến mãi - tăng số lượng
                            break;
                        case 'na objednavku':
                            $variantData['inventoryQuantity'] = 0; // Đặt hàng
                            break;
                        default:
                            $variantData['inventoryQuantity'] = 10; // Mặc định có ít nhất 10
                    }
                } else {
                    // Nếu không có stock_status, mặc định có inventory
                    $variantData['inventoryQuantity'] = 10;
                }

                $variants[] = $variantData;
            }
        } else {
            // Tạo variant mặc định nếu không có variants
            $variants[] = [
                'price' => $product->price,
                'sku' => $product->code,
                'title' => $product->title,
                'inventoryQuantity' => 10,
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

    /**
     * Tạo collection handle theo format Shopify
     */
    private function generateShopifyCollectionHandle($category)
    {
        $handle = '';
        
        // Nếu có parent category
        if ($category->parent) {
            // Format: choose-crystal-chandeliers-by-type-{parent}-{child}
            $parentTitle = str_replace(' ', '-', strtolower($category->parent->title));
            $childTitle = str_replace(' ', '-', strtolower($category->title));
            $handle = 'choose-crystal-chandeliers-by-type-' . $parentTitle . '-' . $childTitle;
        } else {
            // Nếu không có parent, dùng title trực tiếp
            $handle = str_replace(' ', '-', strtolower($category->title));
        }
        
        // Clean up handle
        $handle = preg_replace('/[^a-z0-9-]/', '', $handle);
        $handle = preg_replace('/-+/', '-', $handle);
        $handle = trim($handle, '-');
        
        Log::info("Generated collection handle: " . $handle);
        
        return $handle;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job đồng bộ product '{$this->product->title}' thất bại: " . $exception->getMessage());
    }

    /**
     * Chuyển local path sang public HTTP URL để Shopify có thể tải ảnh
     */
    private function toPublicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        // Nếu đã là URL tuyệt đối
        if (preg_match('#^https?://#i', $path)) {
            return $this->validateImageUrl($path) ? $path : null;
        }
        $normalized = ltrim($path, '/');
        // Loại bỏ prefix 'public/' vì webroot đã là public
        if (stripos($normalized, 'public/') === 0) {
            $normalized = substr($normalized, 7);
        }
        // Xây URL tuyệt đối từ APP_URL, nhưng dùng HTTP thay vì HTTPS để tránh SSL issues
        $base = rtrim(config('app.url'), '/');
        // Thay HTTPS thành HTTP cho local development
        if (strpos($base, 'https://') === 0) {
            $base = 'http://' . substr($base, 8);
        }
        // Thay domain bằng IP để Shopify có thể truy cập
        $base = str_replace('crystallocal.com', '192.168.1.167', $base);
        $url = $base . '/' . $normalized;
        return $this->validateImageUrl($url) ? $url : null;
    }

    /**
     * Chỉ chấp nhận ảnh có đuôi phổ biến để tránh lỗi tải
     */
    private function validateImageUrl(string $url): bool
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['jpg','jpeg','png','gif','webp']);
    }

} 