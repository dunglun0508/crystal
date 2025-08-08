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
            Log::info("Bắt đầu đồng bộ product '{$this->product->title}' lên Shopify");

            // Load relationships
            $this->product->load(['category', 'variants', 'productDetail']);

            // Kiểm tra xem sản phẩm đã tồn tại trên Shopify chưa
            $existingProduct = $shopifyService->findProductByHandle($this->product->slug);
            
            $productData = $this->prepareProductData($this->product);
            
            Log::info("Product data prepared: " . json_encode($productData, JSON_PRETTY_PRINT));
            
            if ($existingProduct['success'] && isset($existingProduct['data']['productByHandle'])) {
                // Cập nhật product đã tồn tại
                $result = $shopifyService->updateProduct(
                    $existingProduct['data']['productByHandle']['id'], 
                    $productData
                );
                $action = 'cập nhật';
            } else {
                // Tạo product mới
                $result = $shopifyService->createProduct($productData);
                $action = 'tạo mới';
            }

            if ($result['success']) {
                Log::info("Đã {$action} sản phẩm '{$this->product->title}' trên Shopify thành công");
            } else {
                Log::error("Lỗi {$action} sản phẩm '{$this->product->title}' trên Shopify: " . $result['error']);
                throw new \Exception($result['error']);
            }

        } catch (\Exception $e) {
            Log::error("Lỗi đồng bộ sản phẩm '{$this->product->title}' lên Shopify: " . $e->getMessage());
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

                // Thêm tên variant nếu có
                if ($variant->name) {
                    $variantData['title'] = $variant->name;
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
            $description .= "<h3>Đặc điểm chính</h3><p>{$product->indicators}</p>";
        }

        // Đặc điểm chi tiết từ ProductDetail
        if ($product->productDetail && $product->productDetail->detail_indicators) {
            $description .= "<h3>Đặc điểm chi tiết</h3><p>{$product->productDetail->detail_indicators}</p>";
        }

        // Thông số kỹ thuật
        if ($product->productDetail && $product->productDetail->specs) {
            $description .= "<h3>Thông số kỹ thuật</h3><div>{$product->productDetail->specs}</div>";
        }

        // Tính năng chính
        if ($product->productDetail && $product->productDetail->key_features) {
            $description .= "<h3>Tính năng chính</h3><div>{$product->productDetail->key_features}</div>";
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
        return in_array($ext, ['jpg','jpeg','png','gif']);
    }
} 