<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Log;

class DeleteAllProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 7200; // 2 giờ
    public $tries = 3;
    public $backoff = [60, 300, 600]; // Retry sau 1, 5, 10 phút

    public function __construct()
    {
        $this->onQueue('shopify-sync');
    }

    public function handle(ShopifyService $shopifyService)
    {
        \Log::info("Start delete all products");
        
        try {
            // Lấy danh sách tất cả products
            $allProducts = $shopifyService->deleteAllProducts();
            
            if ($allProducts === false) {
                throw new \Exception("Failed to fetch products");
            }
            
            if (empty($allProducts)) {
                \Log::info("No products to delete");
                return;
            }
            
            \Log::info("Total products to delete: " . count($allProducts));
            
            // Dispatch job riêng cho từng product để chạy song song
            foreach ($allProducts as $product) {
                DeleteSingleProductJob::dispatch($product['id'], $product['title']);
            }
            
            \Log::info("Dispatched " . count($allProducts) . " individual delete jobs");
            
            // Dispatch job kiểm tra hoàn thành sau 30 giây
            CheckDeletionCompletionJob::dispatch()->delay(now()->addSeconds(30));
            
        } catch (\Exception $e) {
            \Log::error("Failed to delete products: " . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job xóa products thất bại: " . $exception->getMessage());
    }
} 