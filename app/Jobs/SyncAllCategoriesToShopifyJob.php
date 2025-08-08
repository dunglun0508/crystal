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
            \Log::info("Start sync all categories");
            
            $categories = Category::all();
            $totalCategories = $categories->count();
            
            \Log::info("Found {$totalCategories} categories to sync");
            
            foreach ($categories as $category) {
                SyncCategoryToShopifyJob::dispatch($category)
                    ->onQueue('shopify-sync')
                    ->delay(now()->addSeconds(rand(1, 3))); // Random delay 1-3 seconds
            }
            
            \Log::info("Dispatched {$totalCategories} category sync jobs");
            
        } catch (\Throwable $e) {
            \Log::error("Failed to dispatch category sync jobs: " . $e->getMessage());
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