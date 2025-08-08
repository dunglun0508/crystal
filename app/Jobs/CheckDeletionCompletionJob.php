<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ShopifyService;

class CheckDeletionCompletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 phút
    public $tries = 5;
    public $backoff = [30, 60, 120, 300, 600]; // Retry sau 30s, 1p, 2p, 5p, 10p

    protected $checkCount;

    public function __construct($checkCount = 0)
    {
        $this->checkCount = $checkCount;
        $this->onQueue('shopify-sync');
    }

    public function handle(ShopifyService $shopifyService)
    {
        \Log::info("Checking deletion completion (attempt {$this->checkCount})...");
        
        try {
            $remainingProducts = $shopifyService->deleteAllProducts();
            
            if (empty($remainingProducts)) {
                \Log::info("All products deleted successfully! Stopping workers...");
                $this->stopWorkers();
                return;
            }
            
            $remainingCount = count($remainingProducts);
            \Log::info("Still {$remainingCount} products remaining");
            
            // Nếu đã check quá 20 lần (10 phút), dừng lại
            if ($this->checkCount >= 20) {
                \Log::warning("Reached maximum check attempts, stopping workers anyway");
                $this->stopWorkers();
                return;
            }
            
            // Kiểm tra lại sau 30 giây
            self::dispatch($this->checkCount + 1)->delay(now()->addSeconds(30));
            
        } catch (\Exception $e) {
            \Log::error("Error checking completion: " . $e->getMessage());
            
            // Retry sau 1 phút nếu có lỗi
            if ($this->checkCount < 20) {
                self::dispatch($this->checkCount + 1)->delay(now()->addMinute());
            }
        }
    }

    private function stopWorkers()
    {
        \Log::info("Stopping all queue workers...");
        
        // Sử dụng Laravel queue:restart để dừng workers an toàn
        exec('php artisan queue:restart 2>/dev/null', $output, $returnCode);
        
        if ($returnCode === 0) {
            \Log::info("Queue restarted successfully - all workers will stop");
        } else {
            \Log::warning("Could not restart queue automatically");
            \Log::info("Please stop workers manually with: php artisan queue:restart");
        }
    }
} 