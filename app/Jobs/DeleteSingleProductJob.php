<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ShopifyService;

class DeleteSingleProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 phút
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Retry sau 30s, 1p, 2p

    protected $productId;
    protected $productTitle;

    public function __construct($productId, $productTitle)
    {
        $this->productId = $productId;
        $this->productTitle = $productTitle;
        $this->onQueue('shopify-sync');
    }

    public function handle(ShopifyService $shopifyService)
    {
        \Log::info("Deleting product: {$this->productTitle}");
        
        $result = $shopifyService->deleteProduct($this->productId);
        
        if ($result) {
            \Log::info("Deleted: {$this->productTitle}");
        } else {
            \Log::warning("Product not found or already deleted: {$this->productTitle}");
            // Không throw exception nữa, chỉ log warning
        }
    }
} 