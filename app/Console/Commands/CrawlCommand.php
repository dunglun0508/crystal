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

class CrawlCommand extends Command
{
    protected $signature = 'crawl:run {type? : Type of crawl (all, categories, level3, level2, details)} {--category= : Specific category code}';
    protected $description = 'Run crawl jobs for categories, products, and product details';

    public function handle()
    {
        $type = $this->argument('type') ?? 'all';
        $categoryCode = $this->option('category');

        $this->info("Starting crawl jobs for type: {$type}");

        try {
            switch ($type) {
                case 'all':
                    $this->info('Running all crawl jobs...');
                    $dispatchJob = new DispatchCrawlJobs();
                    $dispatchJob->handle();
                    $this->info('All crawl jobs completed successfully!');
                    break;

                case 'categories':
                    $this->info('Running category crawl jobs...');
                    $categoryJob = new CrawlCategoriesJob();
                    $categoryJob->handle();
                    $this->info('Category crawl jobs completed successfully!');
                    break;

                case 'level3':
                    if ($categoryCode) {
                        $category = Category::where('code', $categoryCode)->where('level', '3')->first();
                        if (!$category) {
                            $this->error("Category {$categoryCode} not found or not level 3");
                            return 1;
                        }
                        $level3Job = new CrawlLevel3ProductsJob($category->code, $category->slug);
                        $level3Job->handle();
                        $this->info("Level 3 product crawl job completed for category: {$categoryCode}");
                    } else {
                        $level3Categories = Category::where('level', '3')->get();
                        foreach ($level3Categories as $category) {
                            $level3Job = new CrawlLevel3ProductsJob($category->code, $category->slug);
                            $level3Job->handle();
                            $this->info("Level 3 product crawl job completed for category: {$category->code}");
                        }
                    }
                    break;

                case 'level2':
                    if ($categoryCode) {
                        $category = Category::where('code', $categoryCode)->where('level', '2')->first();
                        if (!$category) {
                            $this->error("Category {$categoryCode} not found or not level 2");
                            return 1;
                        }
                        $level2Job = new CrawlLevel2ProductsJob($category->code, $category->slug);
                        $level2Job->handle();
                        $this->info("Level 2 product crawl job completed for category: {$categoryCode}");
                    } else {
                        $level2Categories = Category::where('level', '2')->get();
                        foreach ($level2Categories as $category) {
                            $level2Job = new CrawlLevel2ProductsJob($category->code, $category->slug);
                            $level2Job->handle();
                            $this->info("Level 2 product crawl job completed for category: {$category->code}");
                        }
                    }
                    break;

                case 'details':
                    $products = Product::select('slug', 'code')
                        ->whereNull('indicators')
                        ->orWhere('indicators', '')
                        ->orderByRaw('CASE WHEN category_code IN (SELECT code FROM categories WHERE level = 3) THEN 0 ELSE 1 END')
                        ->orderBy('code')
                        ->get()
                        ->unique('slug');
                    
                    foreach ($products as $product) {
                        $detailsJob = new CrawlProductDetailsAndVariantsJob($product->slug, $product->code);
                        $detailsJob->handle();
                    }
                    break;

                default:
                    $this->error("Unknown crawl type: {$type}");
                    $this->info("Available types: all, categories, level3, level2, details");
                    return 1;
            }

            $this->info('Crawl jobs completed successfully!');

        } catch (\Exception $e) {
            $this->error("Error running crawl jobs: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 