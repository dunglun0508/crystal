<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Category;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Log;

class SyncCategoryToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $category;
    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopifyService)
    {
        try {
            \Log::info("Sync category: {$this->category->title}");
            
            $categoryData = $this->prepareCategoryData($this->category);
            \Log::info("Checking collection with handle: {$categoryData['handle']}");
            
            // Kiểm tra collection tồn tại với retry logic
            $existingCollection = null;
            $collectionId = null;
            $maxRetries = 3;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $existingCollectionResult = $shopifyService->findCollectionByHandle($categoryData['handle']);
                \Log::info("Collection check attempt {$attempt} result: " . json_encode($existingCollectionResult));
                
                if ($existingCollectionResult && ($existingCollectionResult['success'] ?? false)) {
                    $collectionData = $existingCollectionResult['data']['collectionByHandle'] ?? null;
                    if ($collectionData && isset($collectionData['id'])) {
                        $existingCollection = $collectionData;
                        $collectionId = $collectionData['id'];
                        \Log::info("Found existing collection: {$collectionData['title']} (ID: {$collectionData['id']})");
                        break;
                    } else {
                        \Log::info("Collection not found in Shopify (handle: {$categoryData['handle']})");
                        break;
                    }
                } else {
                    \Log::warning("Failed to check collection existence (attempt {$attempt}): " . json_encode($existingCollectionResult));
                    if ($attempt < $maxRetries) {
                        sleep(2); // Đợi 2 giây trước khi thử lại
                    }
                }
            }
            
            // Nếu tìm thấy collection đã tồn tại, chỉ cập nhật thông tin
            if ($existingCollection) {
                \Log::info("Collection đã tồn tại, cập nhật thông tin: {$this->category->title} (ID: {$existingCollection['id']})");
                $result = $shopifyService->updateCollection($existingCollection['id'], $categoryData);
                
                if ($result && ($result['success'] ?? false)) {
                    \Log::info("Collection updated successfully: {$this->category->title}");
                    $collectionId = $existingCollection['id'];
                } else {
                    \Log::error("Failed to update collection: " . json_encode($result));
                    throw new \Exception("Failed to update collection");
                }
            } else {
                // Chỉ tạo mới khi thực sự không tìm thấy collection
                \Log::info("Tạo collection mới: {$this->category->title} (Handle: {$categoryData['handle']})");
                $result = $shopifyService->createCollection($categoryData);
                
                if ($result && ($result['success'] ?? false) && isset($result['data']['collectionCreate']['collection']['id'])) {
                    $collectionId = $result['data']['collectionCreate']['collection']['id'];
                    \Log::info("Created collection with ID: {$collectionId}");
                } else {
                    \Log::error("Failed to create collection: " . json_encode($result));
                    throw new \Exception("Failed to create collection: " . json_encode($result));
                }
            }

            // Thêm ảnh cho collection nếu có và chưa có ảnh
            if ($collectionId) {
                $this->addCollectionImage($shopifyService, $collectionId);
            }
            
        } catch (\Throwable $e) {
            \Log::error("Sync category failed {$this->category->title}: " . $e->getMessage());
            throw $e;
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
            'handle' => $this->generateShopifyCollectionHandle($category),
            'descriptionHtml' => $description,
            'seo' => [
                'title' => $category->title,
                'description' => "Danh mục {$category->title} - Level {$category->level}"
            ]
        ];
    }

    /**
     * Tạo collection handle theo format Shopify - đồng nhất với SyncProductToShopifyJob
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
        
        \Log::info("Generated collection handle: " . $handle);
        
        return $handle;
    }

    /**
     * Thêm ảnh cho collection
     */
    private function addCollectionImage(ShopifyService $shopifyService, $collectionId)
    {
        // Chỉ thêm ảnh nếu category có ảnh và chưa có ảnh
        if (!empty($this->category->image)) {
            \Log::info("Processing image for category: {$this->category->title}");
            \Log::info("Image path: {$this->category->image}");
            
            $imageUrl = $this->toPublicUrl($this->category->image);
            \Log::info("Converted to public URL: {$imageUrl}");
            
            if ($imageUrl && $this->validateImageUrl($imageUrl)) {
                \Log::info("Image URL is valid, uploading to collection ID: {$collectionId}");
                $uploadResult = $shopifyService->addCollectionImage($collectionId, $imageUrl, $this->category->title);
                
                if ($uploadResult) {
                    \Log::info("Image uploaded successfully for: {$this->category->title}");
                } else {
                    \Log::warning("Failed to upload image for: {$this->category->title} (có thể đã tồn tại)");
                }
            } else {
                \Log::warning("Invalid image URL for category: {$this->category->title} - {$imageUrl}");
            }
        } else {
            \Log::info("No image found for category: {$this->category->title}");
        }
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

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job sync category '{$this->category->title}' failed: " . $exception->getMessage());
    }
} 