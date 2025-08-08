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
            Log::info("Bắt đầu đồng bộ category '{$this->category->title}' lên Shopify");

            // Kiểm tra xem danh mục đã tồn tại trên Shopify chưa
            $existingCollection = $shopifyService->findCollectionByHandle($this->category->slug);
            
            $collectionData = $this->prepareCategoryData($this->category);
            
            if ($existingCollection['success'] && isset($existingCollection['data']['collectionByHandle'])) {
                // Cập nhật collection đã tồn tại
                $result = $shopifyService->updateCollection(
                    $existingCollection['data']['collectionByHandle']['id'], 
                    $collectionData
                );
                $action = 'cập nhật';
            } else {
                // Tạo collection mới
                $result = $shopifyService->createCollection($collectionData);
                $action = 'tạo mới';
            }

            if ($result['success']) {
                Log::info("Đã {$action} danh mục '{$this->category->title}' trên Shopify thành công");
            } else {
                Log::error("Lỗi {$action} danh mục '{$this->category->title}' trên Shopify: " . $result['error']);
                throw new \Exception($result['error']);
            }

        } catch (\Exception $e) {
            Log::error("Lỗi đồng bộ danh mục '{$this->category->title}' lên Shopify: " . $e->getMessage());
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
            'handle' => $category->slug,
            'descriptionHtml' => $description,
            'seo' => [
                'title' => $category->title,
                'description' => "Danh mục {$category->title} - Level {$category->level}"
            ]
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job đồng bộ category '{$this->category->title}' thất bại: " . $exception->getMessage());
    }
} 