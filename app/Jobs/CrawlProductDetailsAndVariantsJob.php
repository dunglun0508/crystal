<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\CrawlController;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class CrawlProductDetailsAndVariantsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3; // Maximum 3 retry attempts
    public $backoff = 60; // Wait 60 seconds between retries
    public $timeout = 300;

    protected $productSlug;
    protected $productCode;

    public function __construct($productSlug, $productCode)
    {
        $this->productSlug = $productSlug;
        $this->productCode = $productCode;
        $this->onQueue('details');
    }

    public function handle()
    {
        try {
            $crawlController = new CrawlController();
            $baseUrl = 'https://www.artcrystal.eu';
            $fullUrl = $baseUrl . $this->productSlug;
            
            // Check if URL is accessible before crawling
            $headers = get_headers($fullUrl, 1);
            if ($headers === false || strpos($headers[0], '200') === false) {
                Log::warning("CrawlProductDetailsAndVariantsJob: URL not accessible - {$fullUrl} - Status: " . ($headers[0] ?? 'Unknown'));
                return; // Skip this product
            }
            
            $productDetail = $crawlController->crawlProductDetailForJob($fullUrl, $this->productSlug);
            
            // Save product detail using code as primary key
            $existingDetail = ProductDetail::where('code', $this->productCode)->first();
            
            if ($existingDetail) {
                // Check if data has changed before updating
                $hasChanges = false;
                $updateData = [
                    'gallery' => $productDetail['gallery'],
                    'gallery_local' => $productDetail['gallery_local'],
                    'detail_indicators' => $productDetail['detail_indicators'],
                    'meta_description' => $productDetail['meta_description'],
                    'long_description' => $productDetail['long_description'],
                    'specs' => $productDetail['specs'],
                    'key_features' => $productDetail['key_features']
                ];
                
                // Compare each field
                foreach ($updateData as $field => $newValue) {
                    if ($existingDetail->$field != $newValue) {
                        $hasChanges = true;
                        break;
                    }
                }
                
                if ($hasChanges) {
                    $existingDetail->update($updateData);
                }
            } else {
                ProductDetail::create([
                    'code' => $this->productCode,
                    'gallery' => $productDetail['gallery'],
                    'gallery_local' => $productDetail['gallery_local'],
                    'detail_indicators' => $productDetail['detail_indicators'],
                    'meta_description' => $productDetail['meta_description'],
                    'long_description' => $productDetail['long_description'],
                    'specs' => $productDetail['specs'],
                    'key_features' => $productDetail['key_features']
                ]);
            }
            
            // Save variants if any
            if (isset($productDetail['variants'])) {
                $variants = json_decode($productDetail['variants'], true);
                if (is_array($variants)) {
                    foreach ($variants as $variant) {
                        // Clean price value - convert empty string to null for decimal field
                        $price = $variant['price'] ?? null;
                        if ($price === '' || $price === null) {
                            $price = null;
                        } else {
                            // Try to convert to numeric value
                            $price = is_numeric($price) ? $price : null;
                        }
                        
                        // Use firstOrCreate instead of updateOrCreate for composite key
                        $existingVariant = ProductVariant::where('code', $this->productSlug)
                            ->where('name', $variant['name'] ?? '')
                            ->first();
                        
                        if ($existingVariant) {
                            // Check if variant data has changed
                            $hasChanges = false;
                            if ($existingVariant->art_no != ($variant['art_no'] ?? '') || 
                                $existingVariant->price != $price) {
                                $hasChanges = true;
                            }
                            
                            if ($hasChanges) {
                                $existingVariant->update([
                                    'art_no' => $variant['art_no'] ?? '',
                                    'price' => $price
                                ]);
                            }
                        } else {
                            ProductVariant::create([
                                'code' => $this->productSlug,
                                'name' => $variant['name'] ?? '',
                                'art_no' => $variant['art_no'] ?? '',
                                'price' => $price
                            ]);
                        }
                    }
                }
            }
            
            Log::info("CrawlProductDetailsAndVariantsJob: Successfully processed product {$this->productSlug} with code {$this->productCode}");
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Check if it's a temporary error (HTTP 500, 502, 503, 504, SSL, network issues)
            if (strpos($errorMessage, 'HTTP/1.1 500') !== false ||
                strpos($errorMessage, 'HTTP/1.1 502') !== false ||
                strpos($errorMessage, 'HTTP/1.1 503') !== false ||
                strpos($errorMessage, 'HTTP/1.1 504') !== false ||
                strpos($errorMessage, 'SSL: Handshake timed out') !== false ||
                strpos($errorMessage, 'Connection timed out') !== false ||
                strpos($errorMessage, 'Failed to open stream') !== false) {
                
                Log::warning("CrawlProductDetailsAndVariantsJob: Temporary error for product {$this->productSlug} (attempt {$this->attempts()}): " . $errorMessage);
                
                // Let Laravel handle retry with $tries and $backoff
                throw $e;
            }
            
            Log::error("CrawlProductDetailsAndVariantsJob: Failed for product {$this->productSlug}: " . $errorMessage);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("CrawlProductDetailsAndVariantsJob: Job failed for product {$this->productSlug}: " . $exception->getMessage());
    }
} 