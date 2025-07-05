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
    public $timeout = 300;
    public $backoff = [15, 30, 60, 120, 300]; // Tăng dần thời gian chờ giữa các retry
    public $maxExceptions = 3;

    /**
     * Tạo một instance job mới.
     */
    public function __construct($categoryCode, $categorySlug)
    {
        $this->categoryCode = $categoryCode;
        $this->categorySlug = $categorySlug;
        $this->onQueue('products');
    }

    /**
     * Thực thi job.
     */
    public function handle(): void
    {
        try {
            // Thêm delay ngẫu nhiên để tránh bị block (1-2 giây)
            sleep(rand(1, 2));
            
            $crawlController = new CrawlController();
            $baseUrl = 'https://www.artcrystal.eu';
            $fullUrl = $baseUrl . $this->categorySlug;
            
            // Kiểm tra URL có thể truy cập trước khi crawl
            if (!$crawlController->checkUrlAccessibility($fullUrl, 30)) {
                Log::warning("CrawlLevel3ProductsJob: URL not accessible - {$fullUrl}");
                return; // Bỏ qua category này
            }
            
            $products = $crawlController->crawlProductsByCategorySlug($fullUrl, $this->categoryCode);
            
            $currentProducts = Product::where('category_code', $this->categoryCode)->get()->keyBy('code');
            
            $toUpsert = [];
            $toAdd = [];
            $toUpdate = [];
            
            foreach ($products as $product) {
                $key = $product['code'];
                $product['category_code'] = $this->categoryCode;
                
                // Làm sạch tất cả các trường dữ liệu để tránh lỗi database
                $product['price'] = (isset($product['price']) && $product['price'] !== '' && $product['price'] !== null) ? $product['price'] : null;
                $product['currency'] = (isset($product['currency']) && $product['currency'] !== '' && $product['currency'] !== null) ? $product['currency'] : null;
                $product['discount'] = (isset($product['discount']) && $product['discount'] !== '' && $product['discount'] !== null) ? $product['discount'] : null;
                $product['image'] = $product['image'] ?? '';
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
            
            // Xóa các sản phẩm không còn tồn tại
            $deletedCount = 0;
            if ($toDelete->count() > 0) {
                $deletedCount = Product::whereIn('id', $toDelete)->delete();
            }
            
            // Thêm mới hoặc cập nhật sản phẩm
            $upsertedCount = 0;
            if (count($toUpsert) > 0) {
                foreach ($toUpsert as $product) {
                    try {
                        $existing = Product::where('code', $product['code'])->first();
                        if ($existing) {
                            $updateData = $product;
                            unset($updateData['slug']);
                            $existing->update($updateData);
                        } else {
                            Product::create($product);
                        }
                        $upsertedCount++;
                    } catch (\Exception $e) {
                        Log::error("CrawlLevel3ProductsJob: Error upserting product {$product['code']}: " . $e->getMessage());
                        continue;
                    }
                }
            }
            
            Log::info("CrawlLevel3ProductsJob: {$this->categorySlug} | Crawled: " . count($products) . " | DB: " . $currentProducts->count() . " | Add: " . count($toAdd) . " | Update: " . count($toUpdate) . " | Delete: " . $deletedCount . " | Total: {$upsertedCount}");
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Kiểm tra có phải lỗi tạm thời không (HTTP errors, SSL, lỗi mạng, timeout)
            if (strpos($errorMessage, 'HTTP request failed') !== false ||
                strpos($errorMessage, 'HTTP/1.1 500') !== false ||
                strpos($errorMessage, 'HTTP/1.1 502') !== false ||
                strpos($errorMessage, 'HTTP/1.1 503') !== false ||
                strpos($errorMessage, 'HTTP/1.1 504') !== false ||
                strpos($errorMessage, 'SSL: Handshake timed out') !== false ||
                strpos($errorMessage, 'Connection timed out') !== false ||
                strpos($errorMessage, 'Failed to open stream') !== false ||
                strpos($errorMessage, 'get_headers') !== false ||
                strpos($errorMessage, 'timeout') !== false ||
                strpos($errorMessage, 'timed out') !== false) {
                
                Log::warning("CrawlLevel3ProductsJob: Temporary error for category {$this->categoryCode} ({$this->categorySlug}) (attempt {$this->attempts()}): " . $errorMessage);
                
                // Ghi lại lỗi vào bảng failed_crawls nếu đã hết số lần retry
                if ($this->attempts() >= $this->tries) {
                    \App\Models\FailedCrawl::updateOrCreate(
                        [
                            'type' => 'category_level3',
                            'identifier' => $this->categoryCode
                        ],
                        [
                            'error' => $errorMessage,
                            'attempts' => \DB::raw('attempts + 1'),
                            'last_attempt_at' => now()
                        ]
                    );
                    Log::error("CrawlLevel3ProductsJob: Permanently failed for category {$this->categoryCode}, added to failed_crawls table");
                }
                
                // Để Laravel xử lý retry với $tries và $backoff
                throw $e;
            }
            
            Log::error("CrawlLevel3ProductsJob: Failed for category {$this->categoryCode} ({$this->categorySlug}): " . $errorMessage);
        }
    }
    
    /**
     * Xử lý khi job thất bại.
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
    

}
