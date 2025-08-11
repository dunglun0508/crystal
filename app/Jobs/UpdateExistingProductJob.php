<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Services\ShopifyService;

class UpdateExistingProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    protected $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle(ShopifyService $shopifyService)
    {
        Log::info("Bắt đầu cập nhật product '{$this->product->title}' trên Shopify");

        try {
            // Tạo handle giống logic sync để tìm đúng product trên Shopify
            $handle = trim(preg_replace('/[^a-z0-9-]/', '-', strtolower(str_replace('/', '-', $this->product->slug))), '-');
            $shopifyProduct = $shopifyService->findProductByHandle($handle);
            
            if (!($shopifyProduct['success'] ?? false) || empty($shopifyProduct['data']['productByHandle'])) {
                Log::error("Không tìm thấy product '{$this->product->title}' trên Shopify (handle: {$handle})");
                return;
            }

            $productId = $shopifyProduct['data']['productByHandle']['id'];

            // Chuẩn bị dữ liệu cập nhật cơ bản
            $updateData = [
                'title' => $this->product->title,
                'productType' => $this->product->category ? $this->product->category->title : 'General',
                'vendor' => 'Crystal Store',
                'tags' => $this->formatProductTags(),
            ];

            // Cập nhật product
            $result = $shopifyService->updateProduct($productId, $updateData);

            if ($result['success'] ?? false) {
                Log::info("Cập nhật thành công product '{$this->product->title}' trên Shopify");

                // Thêm vào collection nếu có
                if ($this->product->category) {
                    $collectionHandle = $this->generateCollectionHandleFromCategory($this->product->category);
                    $collection = $shopifyService->findCollectionByHandle($collectionHandle);
                    if (($collection['success'] ?? false) && isset($collection['data']['collectionByHandle']['id'])) {
                        $shopifyService->addProductToCollection($productId, $collection['data']['collectionByHandle']['id']);
                    }
                }
            } else {
                Log::error("Lỗi cập nhật product '{$this->product->title}' trên Shopify: " . json_encode($result));
            }

        } catch (\Exception $e) {
            Log::error("Lỗi cập nhật product '{$this->product->title}' trên Shopify: " . $e->getMessage());
            throw $e;
        }
    }

    private function prepareUpdateData($existingProduct)
    {
        $updateData = [];

        // Kiểm tra và thêm images nếu chưa có
        if (empty($existingProduct['images']['edges'])) {
            $images = $this->prepareImages();
            if (!empty($images)) {
                $updateData['images'] = $images;
            }
        }

        // Kiểm tra và thêm variants nếu chưa có
        if (empty($existingProduct['variants']['edges'])) {
            $variants = $this->prepareVariants();
            if (!empty($variants)) {
                $updateData['variants'] = $variants;
            }
        }

        // Thêm tags nếu chưa có hoặc cần cập nhật
        if (empty($existingProduct['tags'])) {
            $tags = $this->formatProductTags();
            if (!empty($tags)) {
                $updateData['tags'] = $tags;
            }
        }

        // Thêm collectionId nếu chưa có
        if (empty($existingProduct['collections']['edges'])) {
            if ($this->product->category) {
                $collectionHandle = $this->product->category->slug;
                $shopifyService = app(ShopifyService::class);
                $collection = $shopifyService->findCollectionByHandle($collectionHandle);
                if ($collection && isset($collection['data']['collections']['edges'][0]['node']['id'])) {
                    $updateData['collectionId'] = $collection['data']['collections']['edges'][0]['node']['id'];
                }
            }
        }

        return $updateData;
    }

    private function prepareImages()
    {
        $images = [];
        
        // Hình ảnh chính
        if ($this->product->image) {
            $images[] = [
                'src' => url($this->product->image),
                'altText' => $this->product->title,
                'position' => 1
            ];
        }

        // Gallery từ ProductDetail
        if ($this->product->productDetail && $this->product->productDetail->gallery_local) {
            $galleryImages = json_decode($this->product->productDetail->gallery_local, true);
            if (is_array($galleryImages)) {
                foreach ($galleryImages as $index => $imagePath) {
                    $images[] = [
                        'src' => url($imagePath),
                        'altText' => $this->product->title . ' - Hình ' . ($index + 2),
                        'position' => $index + 2
                    ];
                }
            }
        }

        return $images;
    }

    private function prepareVariants()
    {
        $variants = [];
        
        if ($this->product->variants->count() > 0) {
            foreach ($this->product->variants as $variant) {
                $variantData = [
                    'price' => $variant->price ?? $this->product->price,
                    'sku' => $variant->art_no ?? $this->product->code,
                    'inventoryQuantity' => 0,
                    'weight' => 0,
                    'weightUnit' => 'KILOGRAMS'
                ];

                if ($variant->name) {
                    $variantData['title'] = $variant->name;
                }

                if ($variant->variant_image) {
                    $variantData['image'] = [
                        'src' => url($variant->variant_image),
                        'altText' => $variant->name ?? $this->product->title
                    ];
                }

                // Xử lý stock status
                if ($variant->stock_status) {
                    switch (strtolower($variant->stock_status)) {
                        case 'skladem':
                            $variantData['inventoryQuantity'] = 10;
                            break;
                        case 'akce':
                            $variantData['inventoryQuantity'] = 5;
                            break;
                        default:
                            $variantData['inventoryQuantity'] = 0;
                    }
                }

                $variants[] = $variantData;
            }
        } else {
            // Tạo variant mặc định
            $variants[] = [
                'price' => $this->product->price,
                'sku' => $this->product->code,
                'inventoryQuantity' => 0,
                'weight' => 0,
                'weightUnit' => 'KILOGRAMS'
            ];
        }

        return $variants;
    }

    private function formatProductTags()
    {
        $tags = [];

        if ($this->product->category) {
            $tags[] = $this->product->category->title;
        }

        if ($this->product->discount) {
            $tags[] = 'Khuyến mãi';
        }

        if ($this->product->currency) {
            $tags[] = $this->product->currency;
        }

        if ($this->product->variants->count() > 0) {
            foreach ($this->product->variants as $variant) {
                if ($variant->stock_status) {
                    $tags[] = $variant->stock_status;
                }
            }
        }

        $tags = array_filter(array_unique($tags));
        return implode(', ', $tags);
    }

    private function generateCollectionHandleFromCategory($category): string
    {
        $handle = '';
        if ($category->parent) {
            $parentTitle = str_replace(' ', '-', strtolower($category->parent->title));
            $childTitle = str_replace(' ', '-', strtolower($category->title));
            $handle = 'choose-crystal-chandeliers-by-type-' . $parentTitle . '-' . $childTitle;
        } else {
            $handle = str_replace(' ', '-', strtolower($category->title));
        }
        $handle = preg_replace('/[^a-z0-9-]/', '', $handle);
        $handle = preg_replace('/-+/', '-', $handle);
        return trim($handle, '-');
    }

    public function failed(\Throwable $exception)
    {
        Log::error("Job cập nhật product '{$this->product->title}' thất bại: " . $exception->getMessage());
    }
} 