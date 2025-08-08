<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Category;
use App\Jobs\SyncCategoryToShopifyJob;
use Illuminate\Support\Facades\Log;

class SyncAllCategoriesToShopifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info("Bắt đầu đồng bộ tất cả categories lên Shopify");

            $categories = Category::orderBy('level')->orderBy('title')->get();
            $totalCategories = $categories->count();

            Log::info("Tìm thấy {$totalCategories} categories cần đồng bộ");

            // Dispatch jobs cho từng category
            foreach ($categories as $category) {
                SyncCategoryToShopifyJob::dispatch($category)
                    ->onQueue('shopify-sync')
                    ->delay(now()->addSeconds(rand(1, 5))); // Delay ngẫu nhiên để tránh rate limit
            }

            Log::info("Đã dispatch {$totalCategories} jobs đồng bộ categories");

        } catch (\Exception $e) {
            Log::error("Lỗi khi dispatch jobs đồng bộ categories: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job đồng bộ tất cả categories thất bại: " . $exception->getMessage());
    }
} 