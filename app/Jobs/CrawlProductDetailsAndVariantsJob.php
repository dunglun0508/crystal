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
    
    public $tries = 3; // Giảm số lần retry
    public $timeout = 180; // Giảm timeout
    public $backoff = [10, 20, 40]; // Giảm thời gian chờ retry
    public $maxExceptions = 3;

    protected $productSlug;

    public function __construct($productSlug)
    {
        $this->productSlug = $productSlug;
        $this->onQueue('details');
    }

    public function handle()
    {
        try {
            $crawlController = new CrawlController();
            $baseUrl = 'https://www.artcrystal.eu';
            $fullUrl = $baseUrl . $this->productSlug;
            
            // Thêm delay ngẫu nhiên để tránh bị block (0.5-1 giây)
            sleep(rand(1, 2) / 2);
            
            // Kiểm tra URL có thể truy cập trước khi crawl với timeout dài hơn
            if (!$crawlController->checkUrlAccessibility($fullUrl, 30)) {
                Log::warning("CrawlProductDetailsAndVariantsJob: URL not accessible - {$fullUrl}");
                
                // Ghi vào failed_crawls để theo dõi
                \App\Models\FailedCrawl::updateOrCreate(
                    [
                        'type' => 'product_detail_inaccessible',
                        'identifier' => $this->productSlug
                    ],
                    [
                        'error' => 'URL not accessible - checkUrlAccessibility failed',
                        'attempts' => \DB::raw('attempts + 1'),
                        'last_attempt_at' => now(),
                        'resolved_at' => now() // Đánh dấu là đã xử lý (không retry)
                    ]
                );
                
                return; // Bỏ qua sản phẩm này
            }
            
            $productDetail = $crawlController->crawlProductDetailForJob($fullUrl, $this->productSlug);
            
            // Lưu chi tiết sản phẩm sử dụng slug làm khóa chính
            $existingDetail = ProductDetail::where('code', $this->productSlug)->first();
            
            if ($existingDetail) {
                // Kiểm tra dữ liệu có thay đổi trước khi cập nhật
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
                
                // So sánh từng trường
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
                    'code' => $this->productSlug,
                    'gallery' => $productDetail['gallery'],
                    'gallery_local' => $productDetail['gallery_local'],
                    'detail_indicators' => $productDetail['detail_indicators'],
                    'meta_description' => $productDetail['meta_description'],
                    'long_description' => $productDetail['long_description'],
                    'specs' => $productDetail['specs'],
                    'key_features' => $productDetail['key_features']
                ]);
            }
            
            // Lưu variants nếu có
            if (isset($productDetail['variants'])) {
                $variants = json_decode($productDetail['variants'], true);
                if (is_array($variants)) {
                    foreach ($variants as $variant) {
                        // Làm sạch giá trị price - chuyển chuỗi rỗng thành null cho trường decimal
                        $price = $variant['price'] ?? null;
                        if ($price === '' || $price === null) {
                            $price = null;
                        } else {
                            // Thử chuyển thành giá trị số
                            $price = is_numeric($price) ? $price : null;
                        }
                        
                        // Sử dụng firstOrCreate thay vì updateOrCreate cho khóa tổng hợp
                        $existingVariant = ProductVariant::where('code', $this->productSlug)
                            ->where('name', $variant['name'] ?? '')
                            ->first();
                        
                        if ($existingVariant) {
                            // Kiểm tra dữ liệu variant có thay đổi
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
            Log::info("CrawlProductDetailsAndVariantsJob: Successfully processed product {$this->productSlug}");
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Kiểm tra có phải lỗi tạm thời không (HTTP 500, 502, 503, 504, SSL, lỗi mạng, timeout)
            if (strpos($errorMessage, 'HTTP/1.1 500') !== false ||
                strpos($errorMessage, 'HTTP/1.1 502') !== false ||
                strpos($errorMessage, 'HTTP/1.1 503') !== false ||
                strpos($errorMessage, 'HTTP/1.1 504') !== false ||
                strpos($errorMessage, 'SSL: Handshake timed out') !== false ||
                strpos($errorMessage, 'Connection timed out') !== false ||
                strpos($errorMessage, 'Failed to open stream') !== false ||
                strpos($errorMessage, 'get_headers') !== false ||
                strpos($errorMessage, 'timeout') !== false ||
                strpos($errorMessage, 'timed out') !== false) {
                
                Log::warning("CrawlProductDetailsAndVariantsJob: Temporary error for product {$this->productSlug} (attempt {$this->attempts()}): " . $errorMessage);
                
                // Ghi lại lỗi vào bảng failed_crawls nếu đã hết số lần retry
                if ($this->attempts() >= $this->tries) {
                    \App\Models\FailedCrawl::updateOrCreate(
                        [
                            'type' => 'product_detail',
                            'identifier' => $this->productSlug
                        ],
                        [
                            'error' => $errorMessage,
                            'attempts' => \DB::raw('attempts + 1'),
                            'last_attempt_at' => now()
                        ]
                    );
                    Log::error("CrawlProductDetailsAndVariantsJob: Permanently failed for product {$this->productSlug}, added to failed_crawls table");
                }
                
                // Để Laravel xử lý retry với $tries và $backoff
                throw $e;
            }
            
            // Ghi lỗi 404 vào failed_crawls để theo dõi (không xóa, để job crawl products xử lý)
            if (strpos($errorMessage, 'HTTP/1.1 404') !== false) {
                \App\Models\FailedCrawl::updateOrCreate(
                    [
                        'type' => 'product_detail_404',
                        'identifier' => $this->productSlug
                    ],
                    [
                        'error' => $errorMessage,
                        'attempts' => \DB::raw('attempts + 1'),
                        'last_attempt_at' => now(),
                        'resolved_at' => now() // Đánh dấu là đã xử lý (không retry)
                    ]
                );
                Log::warning("CrawlProductDetailsAndVariantsJob: Product not found (404) - {$this->productSlug}, will be cleaned up by next products crawl");
            } else {
                Log::error("CrawlProductDetailsAndVariantsJob: Failed for product {$this->productSlug}: " . $errorMessage);
            }
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("CrawlProductDetailsAndVariantsJob: Job failed for product {$this->productSlug}: " . $exception->getMessage());
    }
} 