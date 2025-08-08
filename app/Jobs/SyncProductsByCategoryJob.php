<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Category;
use App\Models\Product;
use App\Jobs\SyncProductToShopifyJob;
use Illuminate\Support\Facades\Log;

class SyncProductsByCategoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $category;
    public $timeout = 600; // 10 minutes
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info("Bắt đầu đồng bộ products của category '{$this->category->title}' lên Shopify");

            $products = Product::where('category_code', $this->category->code)
                ->with(['category', 'variants', 'productDetail'])
                ->get();

            $totalProducts = $products->count();

            if ($totalProducts === 0) {
                Log::info("Không tìm thấy products nào trong category '{$this->category->title}'");
                return;
            }

            Log::info("Tìm thấy {$totalProducts} products trong category '{$this->category->title}'");

            // Dispatch jobs cho từng product
            foreach ($products as $product) {
                SyncProductToShopifyJob::dispatch($product)
                    ->onQueue('shopify-sync')
                    ->delay(now()->addSeconds(rand(1, 5))); // Delay ngẫu nhiên để tránh rate limit
            }

            Log::info("Đã dispatch {$totalProducts} jobs đồng bộ products cho category '{$this->category->title}'");

        } catch (\Exception $e) {
            Log::error("Lỗi khi dispatch jobs đồng bộ products cho category '{$this->category->title}': " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Job đồng bộ products cho category '{$this->category->title}' thất bại: " . $exception->getMessage());
    }
} 