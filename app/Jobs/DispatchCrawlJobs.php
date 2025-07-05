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
            Log::info("DispatchCrawlJobs: Starting to run all crawl jobs");
            
            // Bước 1: Chạy job crawl categories ngay lập tức
            $categoryJob = new CrawlCategoriesJob();
            $categoryJob->handle();
            Log::info("DispatchCrawlJobs: Categories job completed");
            
            // Bước 2: Chạy job crawl sản phẩm level 3
            Log::info("=== LEVEL 3 PRODUCTS CRAWL START ===");
            $level3Categories = Category::where('level', '3')->get(['code', 'slug']);
            $level3Processed = 0;
            foreach ($level3Categories as $category) {
                try {
                    $level3Job = new CrawlLevel3ProductsJob($category->code, $category->slug);
                    $level3Job->handle();
                    $level3Processed++;
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    
                    // Kiểm tra có phải lỗi tạm thời không
                    if (strpos($errorMessage, 'HTTP request failed') !== false ||
                        strpos($errorMessage, 'Connection timed out') !== false ||
                        strpos($errorMessage, 'SSL: Handshake timed out') !== false ||
                        strpos($errorMessage, 'Failed to open stream') !== false ||
                        strpos($errorMessage, 'timeout') !== false ||
                        strpos($errorMessage, 'timed out') !== false) {
                        
                        Log::warning("DispatchCrawlJobs: Temporary error for level 3 job {$category->code}: " . $errorMessage);
                        // Không catch exception để Laravel queue xử lý retry
                        throw $e;
                    }
                    
                    Log::error("DispatchCrawlJobs: Permanent error for level 3 job {$category->code}: " . $errorMessage);
                    continue;
                }
            }
            Log::info("DispatchCrawlJobs: Level 3 products completed | Categories: {$level3Categories->count()} | Processed: {$level3Processed}");
            Log::info("=== LEVEL 3 PRODUCTS CRAWL COMPLETE ===");

            Log::info("=== LEVEL 2 PRODUCTS CRAWL START ===");
            // Bước 3: Chạy job crawl sản phẩm level 2 (chỉ các category không có con level 3)
            $level2Categories = Category::where('level', '2')
                ->whereNotIn('code', function($query) {
                    $query->select('parent_code')
                          ->from('categories')
                          ->where('level', '3')
                          ->whereNotNull('parent_code');
                })
                ->get(['code', 'slug']);
            $level2Processed = 0;
            foreach ($level2Categories as $category) {
                try {
                    $level2Job = new CrawlLevel2ProductsJob($category->code, $category->slug);
                    $level2Job->handle();
                    $level2Processed++;
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    
                    // Kiểm tra có phải lỗi tạm thời không
                    if (strpos($errorMessage, 'HTTP request failed') !== false ||
                        strpos($errorMessage, 'Connection timed out') !== false ||
                        strpos($errorMessage, 'SSL: Handshake timed out') !== false ||
                        strpos($errorMessage, 'Failed to open stream') !== false ||
                        strpos($errorMessage, 'timeout') !== false ||
                        strpos($errorMessage, 'timed out') !== false) {
                        
                        Log::warning("DispatchCrawlJobs: Temporary error for level 2 job {$category->code}: " . $errorMessage);
                        // Không catch exception để Laravel queue xử lý retry
                        throw $e;
                    }
                    
                    Log::error("DispatchCrawlJobs: Permanent error for level 2 job {$category->code}: " . $errorMessage);
                    continue;
                }
            }
            Log::info("DispatchCrawlJobs: Level 2 products completed | Categories: {$level2Categories->count()} | Processed: {$level2Processed}");
            Log::info("=== LEVEL 2 PRODUCTS CRAWL COMPLETE ===");

            Log::info("=== PRODUCT DETAILS CRAWL START ===");
            // Bước 4: Chạy job crawl chi tiết sản phẩm và variants
            $products = Product::select('slug')
                ->distinct('slug')
                ->orderByRaw('CASE WHEN category_code IN (SELECT code FROM categories WHERE level = 3) THEN 0 ELSE 1 END')
                ->orderBy('slug')
                ->get();
            
            $totalDetailsProcessed = 0;
            $totalDetailsFailed = 0;
            foreach ($products as $product) {
                try {
                    try {
                        $detailsJob = new CrawlProductDetailsAndVariantsJob($product->slug);
                        $detailsJob->handle();
                        $totalDetailsProcessed++;
                    } catch (\Exception $e) {
                        Log::error("DispatchCrawlJobs: Failed to run details job for {$product->slug}: " . $e->getMessage());
                        $totalDetailsFailed++;
                    }
                } catch (\Exception $e) {
                    Log::error("DispatchCrawlJobs: Failed to run details job for {$product->slug}: " . $e->getMessage());
                    $totalDetailsFailed++;
                }
            }
            Log::info("DispatchCrawlJobs: Product details completed | Products: {$products->count()} | Processed: {$totalDetailsProcessed} | Failed: {$totalDetailsFailed}");
            Log::info("=== PRODUCT DETAILS CRAWL COMPLETE ===");
            Log::info("DispatchCrawlJobs: FINAL SUMMARY | Level3: {$level3Processed}/{$level3Categories->count()} | Level2: {$level2Processed}/{$level2Categories->count()} | Details: {$totalDetailsProcessed}/{$products->count()}");
            
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