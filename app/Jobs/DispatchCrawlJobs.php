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

    public $timeout = 600;
    public $tries = 1;

    public function __construct()
    {
        $this->onQueue('all');
    }

    public function handle()
    {
        try {
            Log::info("DispatchCrawlJobs: Starting to run all crawl jobs");
            
            // Step 1: Run category crawl jobs immediately
            $categoryJob = new CrawlCategoriesJob();
            $categoryJob->handle();
            Log::info("DispatchCrawlJobs: Categories job completed");
            
            // Step 2: Run level 3 product crawl jobs
            $level3Categories = Category::where('level', '3')->get(['code', 'slug']);
            $level3Processed = 0;
            foreach ($level3Categories as $category) {
                try {
                    $level3Job = new CrawlLevel3ProductsJob($category->code, $category->slug);
                    $level3Job->handle();
                    $level3Processed++;
                } catch (\Exception $e) {
                    Log::error("DispatchCrawlJobs: Failed to run level 3 job for {$category->code}: " . $e->getMessage());
                    continue;
                }
            }
            Log::info("DispatchCrawlJobs: Level 3 products completed | Categories: {$level3Categories->count()} | Processed: {$level3Processed}");
            
            // Step 3: Run level 2 product crawl jobs
            $level2Categories = Category::where('level', '2')->get(['code', 'slug']);
            $level2Processed = 0;
            foreach ($level2Categories as $category) {
                try {
                    $level2Job = new CrawlLevel2ProductsJob($category->code, $category->slug);
                    $level2Job->handle();
                    $level2Processed++;
                } catch (\Exception $e) {
                    Log::error("DispatchCrawlJobs: Failed to run level 2 job for {$category->code}: " . $e->getMessage());
                    continue;
                }
            }
            Log::info("DispatchCrawlJobs: Level 2 products completed | Categories: {$level2Categories->count()} | Processed: {$level2Processed}");
            
            // Step 4: Run product details and variants crawl jobs
            $products = Product::select('slug', 'code')
                ->whereNull('indicators')
                ->orWhere('indicators', '')
                ->orderByRaw('CASE WHEN category_code IN (SELECT code FROM categories WHERE level = 3) THEN 0 ELSE 1 END')
                ->orderBy('code')
                ->get()
                ->unique('slug');
            
            $totalDetailsProcessed = 0;
            foreach ($products as $product) {
                try {
                    $detailsJob = new CrawlProductDetailsAndVariantsJob($product->slug, $product->code);
                    $detailsJob->handle();
                    $totalDetailsProcessed++;
                } catch (\Exception $e) {
                    Log::error("DispatchCrawlJobs: Failed to run details job for {$product->slug}: " . $e->getMessage());
                    continue;
                }
            }
            Log::info("DispatchCrawlJobs: Product details completed | Products: {$products->count()} | Processed: {$totalDetailsProcessed}");
            
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