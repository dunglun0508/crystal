<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\CrawlController;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class CrawlCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct()
    {
        $this->onQueue('categories');
    }

    public function handle()
    {
        try {
            Log::info("CrawlCategoriesJob: Starting categories crawl");
            
            $crawlController = new CrawlController();
            $request = new \Illuminate\Http\Request();
            $response = $crawlController->categoriesCrawler($request);
            
            $result = json_decode($response->getContent(), true);
            
            Log::info("CrawlCategoriesJob: Categories crawl completed successfully");
            
            // Sau khi categories hoàn thành, dispatch products jobs
            $this->dispatchProductsJobs();
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("CrawlCategoriesJob: Failed - " . $e->getMessage());
            throw $e;
        }
    }

    private function dispatchProductsJobs()
    {
        try {
            Log::info("CrawlCategoriesJob: Starting to dispatch products jobs");
            
            // Dispatch level 3 products jobs
            $level3Categories = Category::where('level', '3')->get(['code', 'slug']);
            $level3Dispatched = 0;
            
            foreach ($level3Categories as $category) {
                try {
                    \App\Jobs\CrawlLevel3ProductsJob::dispatch($category->code, $category->slug);
                    $level3Dispatched++;
                } catch (\Exception $e) {
                    Log::error("CrawlCategoriesJob: Failed to dispatch level 3 job for {$category->code}: " . $e->getMessage());
                }
            }
            Log::info("CrawlCategoriesJob: Dispatched {$level3Dispatched}/{$level3Categories->count()} level 3 jobs");
            
            // Dispatch level 2 products jobs (chỉ các category không có con level 3)
            $level2Categories = Category::where('level', '2')
                ->whereNotIn('code', function($query) {
                    $query->select('parent_code')
                          ->from('categories')
                          ->where('level', '3')
                          ->whereNotNull('parent_code');
                })
                ->get(['code', 'slug']);
            $level2Dispatched = 0;
            
            foreach ($level2Categories as $category) {
                try {
                    \App\Jobs\CrawlLevel2ProductsJob::dispatch($category->code, $category->slug);
                    $level2Dispatched++;
                } catch (\Exception $e) {
                    Log::error("CrawlCategoriesJob: Failed to dispatch level 2 job for {$category->code}: " . $e->getMessage());
                }
            }
            Log::info("CrawlCategoriesJob: Dispatched {$level2Dispatched}/{$level2Categories->count()} level 2 jobs");
            
            Log::info("CrawlCategoriesJob: Products jobs dispatched successfully");
            
        } catch (\Exception $e) {
            Log::error("CrawlCategoriesJob: Failed to dispatch products jobs: " . $e->getMessage());
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("CrawlCategoriesJob: Job failed: " . $exception->getMessage());
    }
} 