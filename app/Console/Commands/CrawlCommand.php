<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\DispatchCrawlJobs;
use App\Jobs\CrawlLevel3ProductsJob;
use App\Jobs\CrawlLevel2ProductsJob;
use App\Jobs\CrawlProductDetailsAndVariantsJob;
use App\Jobs\CrawlCategoriesJob;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class CrawlCommand extends Command
{
    protected $signature = 'crawl:run {type? : Type of crawl (all, categories, level3, level2, details)} {--category= : Specific category code}';
    protected $description = 'Run crawl jobs for categories, products, and product details. Use crawl:retry-failed to retry failed crawls, crawl:failed-stats to see statistics.';

    public function handle()
    {
        $type = $this->argument('type') ?? 'all';
        $categoryCode = $this->option('category');

        $this->info("Starting crawl jobs for type: {$type}");

        try {
            switch ($type) {
                case 'all':
                    $this->info('Dispatching all crawl jobs to queue...');
                    DispatchCrawlJobs::dispatch();
                    $this->info('All crawl jobs dispatched to queue successfully!');
                    break;

                case 'categories':
                    $this->info('Dispatching category crawl jobs to queue...');
                    CrawlCategoriesJob::dispatch();
                    $this->info('Category crawl jobs dispatched to queue successfully!');
                    break;

                case 'level3':
                    Log::info("=== LEVEL 3 PRODUCTS CRAWL START ===");
                    if ($categoryCode) {
                        $category = Category::where('code', $categoryCode)->where('level', '3')->first();
                        if (!$category) {
                            $this->error("Category {$categoryCode} not found or not level 3");
                            return 1;
                        }
                        CrawlLevel3ProductsJob::dispatch($category->code, $category->slug);
                        $this->info("Level 3 job dispatched for category: {$categoryCode}");
                    } else {
                        $level3Categories = Category::where('level', '3')->get();
                        foreach ($level3Categories as $category) {
                            CrawlLevel3ProductsJob::dispatch($category->code, $category->slug);
                        }
                        $this->info("Dispatched {$level3Categories->count()} level 3 jobs to queue");
                    }
                    Log::info("=== LEVEL 3 PRODUCTS CRAWL DISPATCHED ===");
                    break;

                case 'level2':
                    Log::info("=== LEVEL 2 PRODUCTS CRAWL START ===");
                    if ($categoryCode) {
                        $category = Category::where('code', $categoryCode)->where('level', '2')->first();
                        if (!$category) {
                            $this->error("Category {$categoryCode} not found or not level 2");
                            return 1;
                        }
                        CrawlLevel2ProductsJob::dispatch($category->code, $category->slug);
                        $this->info("Level 2 job dispatched for category: {$categoryCode}");
                    } else {
                        // Chỉ lấy các category level 2 mà không có category con level 3
                        $level2Categories = Category::where('level', '2')
                            ->whereNotIn('code', function($query) {
                                $query->select('parent_code')
                                      ->from('categories')
                                      ->where('level', '3')
                                      ->whereNotNull('parent_code');
                            })
                            ->get();
                        foreach ($level2Categories as $category) {
                            CrawlLevel2ProductsJob::dispatch($category->code, $category->slug);
                        }
                        $this->info("Dispatched {$level2Categories->count()} level 2 jobs to queue");
                    }
                    Log::info("=== LEVEL 2 PRODUCTS CRAWL DISPATCHED ===");
                    break;

                case 'details':
                    Log::info("=== PRODUCT DETAILS AND VARIANTS CRAWL START ===");
                    $products = Product::select('slug')
                        ->distinct('slug')
                        ->orderByRaw('CASE WHEN category_code IN (SELECT code FROM categories WHERE level = 3) THEN 0 ELSE 1 END')
                        ->orderBy('slug')
                        ->get();
                    
                    $this->info("Dispatching {$products->count()} detail jobs to queue...");
                    
                    foreach ($products as $product) {
                        CrawlProductDetailsAndVariantsJob::dispatch($product->slug);
                    }
                    
                    $this->info('Product details jobs dispatched to queue successfully!');
                    Log::info("=== PRODUCT DETAILS AND VARIANTS CRAWL DISPATCHED ===");
                    break;
                default:
                    $this->error("Unknown crawl type: {$type}");
                    $this->info("Available types: all, categories, level3, level2, details");
                    return 1;
            }

            $this->info('Crawl jobs dispatched to queue successfully!');

        } catch (\Exception $e) {
            $this->error("Error dispatching crawl jobs: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 