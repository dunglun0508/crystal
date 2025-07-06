<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class DispatchCrawlJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // Tăng timeout lên 1 giờ
    public $tries = 3; // Thêm retry cho dispatch job
    public $backoff = [300, 600, 1200]; // 5m, 10m, 20m

    public function __construct()
    {
        $this->onQueue('all');
    }

    public function handle()
    {
        try {
            Log::info("DispatchCrawlJobs: Starting to dispatch crawl jobs in sequence");
            
            // Bước 1: Chỉ dispatch job crawl categories trước
            // Các job products và details sẽ được dispatch sau khi categories hoàn thành
            try {
                CrawlCategoriesJob::dispatch();
                Log::info("DispatchCrawlJobs: Categories job dispatched - will dispatch products after categories complete");
            } catch (\Exception $e) {
                Log::error("DispatchCrawlJobs: Failed to dispatch categories job: " . $e->getMessage());
                throw $e; // Không thể tiếp tục nếu categories fail
            }
            
            Log::info("DispatchCrawlJobs: Only categories job dispatched. Products and details will be dispatched after categories complete.");
            
        } catch (\Exception $e) {
            Log::error("DispatchCrawlJobs: Failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("DispatchCrawlJobs: Job failed: " . $exception->getMessage());
    }
} 