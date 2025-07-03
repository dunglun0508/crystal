<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\CrawlController;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class CrawlLevel2ProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    protected $categoryCode;
    protected $categorySlug;

    public function __construct($categoryCode, $categorySlug)
    {
        $this->categoryCode = $categoryCode;
        $this->categorySlug = $categorySlug;
        $this->onQueue('products');
    }

    public function handle()
    {
        try {
            Log::info("CrawlLevel2ProductsJob: Starting for category ({$this->categoryCode})");
            
            $crawlController = new CrawlController();
            $baseUrl = 'https://www.artcrystal.eu';
            $products = $crawlController->crawlLevel2ProductsByCategorySlug($baseUrl . $this->categorySlug, $this->categoryCode);
            
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
                        Log::error("CrawlLevel2ProductsJob: Error upserting product {$product['code']}: " . $e->getMessage());
                        continue;
                    }
                }
            }
            
            Log::info("CrawlLevel2ProductsJob: {$this->categorySlug} | Crawled: " . count($products) . " | DB: " . $currentProducts->count() . " | Add: " . count($toAdd) . " | Update: " . count($toUpdate) . " | Delete: " . $deletedCount . " | Total: {$upsertedCount}");
            
        } catch (\Exception $e) {
            Log::error("CrawlLevel2ProductsJob: Failed for category {$this->categoryCode} ({$this->categorySlug}): " . $e->getMessage());
            // Don't throw the exception to allow other jobs to continue
            // throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("CrawlLevel2ProductsJob: Job failed for category {$this->categoryCode}: " . $exception->getMessage());
    }
} 