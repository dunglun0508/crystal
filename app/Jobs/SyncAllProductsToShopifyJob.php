<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Jobs\SyncProductToShopifyJob;
use Illuminate\Support\Facades\Log;

class SyncAllProductsToShopifyJob implements ShouldQueue
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
            \Log::info("Start sync all products");
            
            $products = Product::with(['category', 'variants', 'productDetail'])->get();
            $totalProducts = $products->count();
            
            \Log::info("Found {$totalProducts} products to sync");
            
            foreach ($products as $product) {
                SyncProductToShopifyJob::dispatch($product)
                    ->onQueue('shopify-sync')
                    ->delay(now()->addSeconds(rand(1, 3))); // Random delay 1-3 seconds
            }
            
            \Log::info("Dispatched {$totalProducts} product sync jobs");
            
        } catch (\Throwable $e) {
            \Log::error("Failed to dispatch product sync jobs: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job đồng bộ tất cả products thất bại: " . $exception->getMessage());
    }
} 