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
use App\Http\Controllers\CrawlController;

class CrawlLevel3ProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $categoryCode;
    protected $categorySlug;
    
    public $tries = 5;
    public $timeout = 600;
    public $backoff = [60, 120, 300, 600];
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($categoryCode, $categorySlug)
    {
        $this->categoryCode = $categoryCode;
        $this->categorySlug = $categorySlug;
        $this->onQueue('products');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("CrawlLevel3ProductsJob: Starting for category ({$this->categoryCode})");
            
            $crawlController = new CrawlController();
            $baseUrl = 'https://www.artcrystal.eu';
            $products = $crawlController->crawlProductsByCategorySlug($baseUrl . $this->categorySlug, $this->categoryCode);
            
            $currentProducts = Product::where('category_code', $this->categoryCode)->get()->keyBy('code');
            
            $toUpsert = [];
            $toAdd = [];
            $toUpdate = [];
            
            foreach ($products as $product) {
                $key = $product['code'];
                $product['category_code'] = $this->categoryCode;
                
                // Clean all data fields to prevent database errors
                $product['price'] = (isset($product['price']) && $product['price'] !== '' && $product['price'] !== null) ? $product['price'] : null;
                $product['currency'] = (isset($product['currency']) && $product['currency'] !== '' && $product['currency'] !== null) ? $product['currency'] : null;
                $product['discount'] = (isset($product['discount']) && $product['discount'] !== '' && $product['discount'] !== null) ? $product['discount'] : null;
                $product['image'] = (isset($product['image']) && $product['image'] !== '' && $product['image'] !== null) ? $product['image'] : 'default-product.jpg';
                $product['title'] = (isset($product['title']) && $product['title'] !== '' && $product['title'] !== null) ? $product['title'] : 'No Title';
                
                if (!isset($currentProducts[$key])) {
                    $toAdd[] = $product;
                    $toUpsert[] = $product;
                } elseif ($currentProducts[$key]->title !== $product['title'] ||
                         $currentProducts[$key]->price !== $product['price'] ||
                         $currentProducts[$key]->image !== $product['image']) {
                    $toUpdate[] = $product;
                    $toUpsert[] = $product;
                }
            }
            
            $productCodes = array_column($products, 'code');
            $toDelete = $currentProducts->filter(function($prod) use ($productCodes) {
                return !in_array($prod->code, $productCodes);
            })->pluck('id');
            
            // Delete products no longer present
            $deletedCount = 0;
            if ($toDelete->count() > 0) {
                $deletedCount = Product::whereIn('id', $toDelete)->delete();
            }
            
            // Upsert products
            $upsertedCount = 0;
            if (count($toUpsert) > 0) {
                foreach ($toUpsert as $product) {
                    try {
                        Product::updateOrCreate(
                            ['code' => $product['code']],
                            $product
                        );
                        $upsertedCount++;
                    } catch (\Exception $e) {
                        Log::error("CrawlLevel3ProductsJob: Error upserting product {$product['code']}: " . $e->getMessage());
                        continue;
                    }
                }
            }
            
            Log::info("CrawlLevel3ProductsJob: {$this->categorySlug} | Crawled: " . count($products) . " | DB: " . $currentProducts->count() . " | Add: " . count($toAdd) . " | Update: " . count($toUpdate) . " | Delete: " . $deletedCount . " | Total: {$upsertedCount}");
            
        } catch (\Exception $e) {
            Log::error("CrawlLevel3ProductsJob: Failed for category {$this->categoryCode} ({$this->categorySlug}): " . $e->getMessage());
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("CrawlProductsJob: Job failed permanently for category {$this->categorySlug} ({$this->categoryCode})", [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'attempts' => $this->attempts(),
            'exceptions' => $this->exceptions()
        ]);
    }
    
    /**
     * Get the number of times the job may be attempted.
     */
    public function retries()
    {
        return 5;
    }
    
    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff()
    {
        return [60, 120, 300, 600];
    }
    
    /**
     * Determine the time at which the job should timeout.
     */
    public function retryAfter()
    {
        return 600;
    }
}
