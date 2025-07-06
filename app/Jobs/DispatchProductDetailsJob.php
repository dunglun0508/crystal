<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class DispatchProductDetailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 phút
    public $tries = 3;
    public $backoff = [300, 600, 1200];

    public function __construct()
    {
        $this->onQueue('details');
    }

    public function handle()
    {
        try {
            Log::info("DispatchProductDetailsJob: Starting to dispatch product details jobs");
            
            $products = Product::select('slug')
                ->distinct('slug')
                ->orderByRaw('CASE WHEN category_code IN (SELECT code FROM categories WHERE level = 3) THEN 0 ELSE 1 END')
                ->orderBy('slug')
                ->get();
            
            $detailsDispatched = 0;
            $detailsFailed = 0;
            
            foreach ($products as $product) {
                try {
                    CrawlProductDetailsAndVariantsJob::dispatch($product->slug);
                    $detailsDispatched++;
                } catch (\Exception $e) {
                    Log::error("DispatchProductDetailsJob: Failed to dispatch details job for {$product->slug}: " . $e->getMessage());
                    $detailsFailed++;
                    
                    // Kiểm tra có phải lỗi tạm thời không
                    if (strpos($e->getMessage(), 'Connection timed out') !== false ||
                        strpos($e->getMessage(), 'Failed to open stream') !== false ||
                        strpos($e->getMessage(), 'timeout') !== false) {
                        Log::warning("DispatchProductDetailsJob: Temporary error for details dispatch {$product->slug}, will retry");
                        throw $e; // Retry toàn bộ job
                    }
                }
            }
            
            Log::info("DispatchProductDetailsJob: Dispatched {$detailsDispatched}/{$products->count()} detail jobs | Failed: {$detailsFailed}");
            Log::info("DispatchProductDetailsJob: All product details jobs dispatched successfully");
            
        } catch (\Exception $e) {
            Log::error("DispatchProductDetailsJob: Failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("DispatchProductDetailsJob: Job failed: " . $exception->getMessage());
    }
} 