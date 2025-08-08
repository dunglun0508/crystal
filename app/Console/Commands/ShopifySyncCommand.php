<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Product;
use App\Jobs\SyncAllCategoriesToShopifyJob;
use App\Jobs\SyncAllProductsToShopifyJob;
use App\Jobs\SyncProductsByCategoryJob;
use App\Jobs\UpdateExistingProductJob;
use App\Jobs\DeleteAllProductsJob;
use App\Services\ShopifyService;

class ShopifySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:sync 
                            {type : Type of sync (categories|products|all|category-products|delete-all-products|count-products|update-existing|update-category-images|list-collections|check-duplicates|check-sync-status|test-product|list-locations|publish-products)}
                            {--category= : Category code for category-products sync}
                            {--product= : Product code for test-product sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data to Shopify using background jobs';

    /**
     * Shopify service instance
     *
     * @var ShopifyService
     */
    private $shopifyService;

    /**
     * Create a new command instance.
     */
    public function __construct(ShopifyService $shopifyService)
    {
        parent::__construct();
        $this->shopifyService = $shopifyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        switch ($type) {
            case 'categories':
                $this->syncCategories();
                break;
            case 'products':
                $this->syncProducts();
                break;
            case 'all':
                $this->syncAll();
                break;
            case 'category-products':
                $this->syncCategoryProducts();
                break;
            case 'update-existing':
                $this->updateExistingProducts();
                break;
            case 'delete-all-products':
                $this->deleteAllProducts();
                break;
            case 'count-products':
                $this->countProducts();
                break;
            case 'update-category-images':
                $this->updateCategoryImages();
                break;
            case 'list-collections':
                $this->listCollections();
                break;
            case 'check-duplicates':
                $this->checkDuplicateCollections();
                break;
            case 'check-sync-status':
                $this->checkSyncStatus();
                break;
            case 'test-product':
                $this->testProductSync();
                break;
            case 'list-locations':
                $this->listLocations();
                break;
            case 'publish-products':
                $this->publishProducts();
                break;
            default:
                $this->error("Invalid sync type: {$type}");
                $this->info("Available types: categories, products, all, category-products, update-existing, delete-all-products, count-products, update-category-images, list-collections, check-duplicates, check-sync-status, test-product, list-locations, publish-products");
                return 1;
        }

        return 0;
    }

    /**
     * Sync all categories
     */
    private function syncCategories()
    {
        $totalCategories = Category::count();
        
        $this->info("Found {$totalCategories} categories to sync");
        
        if ($this->confirm('Do you want to proceed with syncing all categories?')) {
            SyncAllCategoriesToShopifyJob::dispatch()
                ->onQueue('shopify-sync');
            
            $this->info('Job dispatched successfully!');
            $this->info('Run: php artisan queue:work --queue=shopify-sync');
        }
    }

    /**
     * Sync all products
     */
    private function syncProducts()
    {
        $totalProducts = Product::count();
        
        $this->info("Found {$totalProducts} products to sync");
        
        if ($this->confirm('Do you want to proceed with syncing all products?')) {
            SyncAllProductsToShopifyJob::dispatch()
                ->onQueue('shopify-sync');
            
            $this->info('Job dispatched successfully!');
            $this->info('Run: php artisan queue:work --queue=shopify-sync');
        }
    }

    /**
     * Sync tất cả categories và products
     */
    private function syncAll()
    {
        $this->info('Dispatching jobs to sync all categories and products...');
        
        SyncAllCategoriesToShopifyJob::dispatch()->onQueue('shopify-sync');
        SyncAllProductsToShopifyJob::dispatch()->onQueue('shopify-sync');
        
        $this->info('Jobs dispatched successfully!');
        $this->info('Run: php artisan queue:work --queue=shopify-sync');
    }

    /**
     * Sync products theo category
     */
    private function syncCategoryProducts()
    {
        $categoryCode = $this->option('category');
        
        if (!$categoryCode) {
            $this->error('Category code is required for category-products sync');
            $this->info('Usage: php artisan shopify:sync category-products --category=CATEGORY_CODE');
            return;
        }
        
        $this->info("Dispatching job to sync products for category: {$categoryCode}");
        
        SyncProductsByCategoryJob::dispatch($categoryCode)->onQueue('shopify-sync');
        
        $this->info('Job dispatched successfully!');
        $this->info('Run: php artisan queue:work --queue=shopify-sync');
    }

    private function updateExistingProducts()
    {
        $totalProducts = Product::count();
        $this->info("Found {$totalProducts} products to update");
        $this->info("This will update existing products on Shopify with:");
        $this->info("- Images (if missing)");
        $this->info("- Variants (if missing)");
        $this->info("- Inventory (if missing)");
        $this->info("- Collection assignment (if missing)");
        $this->info("- Tags (if missing)");
        
        if ($this->confirm('Do you want to proceed with updating existing products?')) {
            // Dispatch job for each product to update
            $products = Product::with(['category', 'productDetail', 'variants'])->get();
            
            foreach ($products as $product) {
                UpdateExistingProductJob::dispatch($product)
                    ->onQueue('shopify-sync')
                    ->delay(now()->addSeconds(rand(1, 5))); // Random delay to avoid rate limits
            }
            
            $this->info('Update jobs dispatched successfully!');
            $this->info('Run: php artisan queue:work --queue=shopify-sync');
        }
    }

    /**
     * Xóa tất cả products trên Shopify
     */
    private function deleteAllProducts()
    {
        $this->info('Dispatching job to delete all products from Shopify...');
        
        DeleteAllProductsJob::dispatch()->onQueue('shopify-sync');
        
        $this->info('Job dispatched successfully!');
        $this->info('Run: php artisan queue:work --queue=shopify-sync');
        $this->info('Check logs for progress...');
    }

    /**
     * Đếm tổng số products trên Shopify
     */
    private function countProducts()
    {
        $this->info('Counting products on Shopify...');
        
        $shopifyService = app(ShopifyService::class);
        $count = $shopifyService->countProducts();
        
        if ($count !== false) {
            $this->info("Total products on Shopify: {$count}");
        } else {
            $this->error('Failed to count products! Check logs for errors.');
        }
    }

    /**
     * Update category images in database
     */
    private function updateCategoryImages()
    {
        $this->info('Updating category images...');

        // Lấy danh sách ảnh trong thư mục categories
        $imageFiles = glob('public/images/categories/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        $imageMap = [];

        foreach ($imageFiles as $imageFile) {
            $filename = basename($imageFile);
            
            // Xử lý tên file để tạo key mapping
            $key = $filename;
            // Loại bỏ prefix và suffix
            $key = str_replace(['-c-', '_2.jpg', '_2.webp'], '', $key);
            // Thay dấu gạch ngang bằng dấu cách
            $key = str_replace('-', ' ', $key);
            // Loại bỏ các prefix phổ biến
            $key = str_replace(['crystal glass ', 'special offers ', 'choose crystal chandeliers by room ', 'choose crystal chandeliers by style ', 'choose crystal chandeliers by type '], '', $key);
            
            $imageMap[$key] = 'images/categories/' . $filename;
        }

        $this->info("Found " . count($imageMap) . " image files");

        // Debug: in ra mapping để kiểm tra
        foreach ($imageMap as $key => $path) {
            $this->info("Mapping: '{$key}' -> '{$path}'");
        }

        // Cập nhật categories
        $categories = Category::all();
        $updated = 0;

        foreach ($categories as $category) {
            $title = strtolower($category->title);
            
            // Tìm ảnh phù hợp với nhiều cách matching
            $matchedImage = null;
            
            // Cách 1: Exact match
            foreach ($imageMap as $slug => $imagePath) {
                if (strtolower($slug) === $title) {
                    $matchedImage = $imagePath;
                    break;
                }
            }
            
            // Cách 2: Contains match
            if (!$matchedImage) {
                foreach ($imageMap as $slug => $imagePath) {
                    if (strpos($title, strtolower($slug)) !== false || strpos(strtolower($slug), $title) !== false) {
                        $matchedImage = $imagePath;
                        break;
                    }
                }
            }
            
            // Cách 3: Word match (tìm từ khóa chính)
            if (!$matchedImage) {
                $titleWords = explode(' ', $title);
                foreach ($imageMap as $slug => $imagePath) {
                    $slugWords = explode(' ', strtolower($slug));
                    foreach ($titleWords as $word) {
                        if (strlen($word) > 2 && in_array($word, $slugWords)) {
                            $matchedImage = $imagePath;
                            break 2;
                        }
                    }
                }
            }
            
            if ($matchedImage) {
                $category->update(['image' => $matchedImage]);
                $this->info("Updated {$category->title} -> {$matchedImage}");
                $updated++;
            } else {
                $this->warn("No image found for: {$category->title}");
            }
        }

        $this->info("\nUpdated {$updated} categories with images");
        $this->info('Done!');
    }

    /**
     * List all collections on Shopify
     */
    private function listCollections()
    {
        $this->info('Listing collections on Shopify...');
        
        $shopifyService = app(ShopifyService::class);
        
        // Lấy danh sách collections
        $query = '
        query {
            collections(first: 250) {
                edges {
                    node {
                        id
                        title
                        handle
                        createdAt
                    }
                }
            }
        }';
        
        $result = $shopifyService->makeGraphQLRequest($query);
        
        if ($result && ($result['success'] ?? false)) {
            $collections = $result['data']['collections']['edges'] ?? [];
            $this->info("Found " . count($collections) . " collections:");
            
            foreach ($collections as $edge) {
                $collection = $edge['node'];
                $this->info("- {$collection['title']} (Handle: {$collection['handle']}, ID: {$collection['id']})");
            }
        } else {
            $this->error('Failed to fetch collections: ' . json_encode($result));
        }
    }

    /**
     * Kiểm tra và xóa duplicate collections
     */
    private function checkDuplicateCollections()
    {
        $this->info('Kiểm tra duplicate collections trên Shopify...');

        $shopifyService = app(ShopifyService::class);

        // Fetch collections từ Shopify
        $query = '
        query {
            collections(first: 250) {
                edges {
                    node {
                        id
                        title
                        handle
                        createdAt
                    }
                }
            }
        }';

        $shopifyCollectionsResult = $shopifyService->makeGraphQLRequest($query);

        if ($shopifyCollectionsResult && ($shopifyCollectionsResult['success'] ?? false)) {
            $shopifyCollections = $shopifyCollectionsResult['data']['collections']['edges'] ?? [];
            $this->info("Tìm thấy " . count($shopifyCollections) . " collections trên Shopify.");

            $localCategories = Category::all();
            $this->info("Tìm thấy " . $localCategories->count() . " categories trong database local.");

            // Tạo map của Shopify collections theo handle
            $shopifyCollectionMap = [];
            $duplicateHandles = [];
            
            foreach ($shopifyCollections as $edge) {
                $collection = $edge['node'];
                $handle = $collection['handle'];
                
                if (isset($shopifyCollectionMap[$handle])) {
                    // Tìm thấy duplicate
                    if (!isset($duplicateHandles[$handle])) {
                        $duplicateHandles[$handle] = [];
                    }
                    $duplicateHandles[$handle][] = $collection;
                } else {
                    $shopifyCollectionMap[$handle] = $collection;
                }
            }

            // Hiển thị kết quả
            if (empty($duplicateHandles)) {
                $this->info("✅ Không tìm thấy duplicate collections!");
            } else {
                $this->warn("❌ Tìm thấy " . count($duplicateHandles) . " handles bị duplicate:");
                
                foreach ($duplicateHandles as $handle => $collections) {
                    $this->error("Handle: {$handle}");
                    foreach ($collections as $collection) {
                        $this->line("  - ID: {$collection['id']}, Title: {$collection['title']}, Created: {$collection['createdAt']}");
                    }
                }
                
                if ($this->confirm('Bạn có muốn xóa các collections duplicate không? (sẽ giữ lại collection cũ nhất)')) {
                    $this->deleteDuplicateCollections($duplicateHandles);
                }
            }

            // Kiểm tra collections thiếu
            $missingCollections = [];
            foreach ($localCategories as $category) {
                $expectedHandle = $this->generateExpectedHandle($category);
                if (!isset($shopifyCollectionMap[$expectedHandle])) {
                    $missingCollections[] = [
                        'category' => $category,
                        'expected_handle' => $expectedHandle
                    ];
                }
            }

            if (!empty($missingCollections)) {
                $this->warn("⚠️  Tìm thấy " . count($missingCollections) . " collections thiếu:");
                foreach ($missingCollections as $missing) {
                    $this->line("  - Category: {$missing['category']->title}, Expected Handle: {$missing['expected_handle']}");
                }
            } else {
                $this->info("✅ Tất cả categories local đều có collection tương ứng trên Shopify!");
            }

        } else {
            $this->error("Không thể kết nối đến Shopify API");
        }
    }

    /**
     * Xóa duplicate collections
     */
    public function deleteDuplicateCollections($duplicateHandles)
    {
        $shopifyService = app(ShopifyService::class);
        $deletedCount = 0;

        foreach ($duplicateHandles as $handle => $collections) {
            // Sắp xếp theo thời gian tạo, giữ lại collection cũ nhất
            usort($collections, function($a, $b) {
                return strtotime($a['createdAt']) - strtotime($b['createdAt']);
            });

            // Xóa tất cả collections trừ collection đầu tiên (cũ nhất)
            for ($i = 1; $i < count($collections); $i++) {
                $collection = $collections[$i];
                $this->info("Đang xóa collection: {$collection['title']} (ID: {$collection['id']})");
                
                $result = $shopifyService->deleteCollection($collection['id']);
                if ($result && ($result['success'] ?? false)) {
                    $this->info("✅ Đã xóa collection: {$collection['title']}");
                    $deletedCount++;
                } else {
                    $this->error("❌ Không thể xóa collection: {$collection['title']}");
                }
            }
        }

        $this->info("Hoàn thành! Đã xóa {$deletedCount} duplicate collections.");
    }

    /**
     * Kiểm tra trạng thái sync
     */
    private function checkSyncStatus()
    {
        $this->info('Kiểm tra trạng thái sync...');

        $shopifyService = app(ShopifyService::class);

        // Lấy danh sách collections từ Shopify
        $query = '
        query {
            collections(first: 250) {
                edges {
                    node {
                        id
                        title
                        handle
                        createdAt
                    }
                }
            }
        }';

        $shopifyCollectionsResult = $shopifyService->makeGraphQLRequest($query);

        if ($shopifyCollectionsResult && ($shopifyCollectionsResult['success'] ?? false)) {
            $shopifyCollections = $shopifyCollectionsResult['data']['collections']['edges'] ?? [];
            $this->info("Tìm thấy " . count($shopifyCollections) . " collections trên Shopify.");

            $localCategories = Category::all();
            $this->info("Tìm thấy " . $localCategories->count() . " categories trong database local.");

            // Tạo map của Shopify collections theo handle
            $shopifyCollectionMap = [];
            $missingCollections = [];
            
            foreach ($shopifyCollections as $edge) {
                $collection = $edge['node'];
                $handle = $collection['handle'];
                $shopifyCollectionMap[$handle] = $collection;
            }

            // Kiểm tra collections thiếu
            foreach ($localCategories as $category) {
                $expectedHandle = $this->generateExpectedHandle($category);
                if (!isset($shopifyCollectionMap[$expectedHandle])) {
                    $missingCollections[] = [
                        'category' => $category,
                        'expected_handle' => $expectedHandle
                    ];
                }
            }

            if (empty($missingCollections)) {
                $this->info("✅ Tất cả categories local đều có collection tương ứng trên Shopify!");
                $this->info("✅ Tất cả collections trên Shopify đều có category tương ứng trong database!");
            } else {
                $this->warn("⚠️  Tìm thấy " . count($missingCollections) . " collections thiếu:");
                foreach ($missingCollections as $missing) {
                    $this->line("  - Category: {$missing['category']->title}, Expected Handle: {$missing['expected_handle']}");
                }
                $this->warn("⚠️  Tìm thấy " . count($missingCollections) . " collections thiếu trên Shopify:");
                foreach ($missingCollections as $missing) {
                    $this->line("  - Category: {$missing['category']->title}, Expected Handle: {$missing['expected_handle']}");
                }
            }

        } else {
            $this->error("Không thể kết nối đến Shopify API");
        }
    }

    /**
     * Tạo expected handle cho category (đồng nhất với logic trong jobs)
     */
    public function generateExpectedHandle($category)
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
        $handle = trim($handle, '-');
        
        return $handle;
    }

    /**
     * Test sync for a specific product
     */
    private function testProductSync()
    {
        $productCode = $this->option('product');

        if (!$productCode) {
            $this->error('Product code is required for test-product sync');
            $this->info('Usage: php artisan shopify:sync test-product --product=PRODUCT_CODE');
            return;
        }

        // Tìm product theo code
        $product = Product::where('code', $productCode)->first();
        
        if (!$product) {
            $this->error("Product not found with code: {$productCode}");
            return;
        }

        $this->info("Found product: {$product->title}");
        $this->info("Dispatching job to sync product: {$productCode}");

        // Dispatch SyncProductToShopifyJob với Product model
        \App\Jobs\SyncProductToShopifyJob::dispatch($product)->onQueue('shopify-sync');
        
        $this->info("Job dispatched successfully!");
        $this->info("Run: php artisan queue:work --queue=shopify-sync");
    }

    /**
     * List all locations from Shopify
     */
    private function listLocations()
    {
        $this->info('Listing locations from Shopify...');

        $shopifyService = app(ShopifyService::class);

        // Lấy danh sách locations với fields hợp lệ
        $query = '
        query {
            locations(first: 250) {
                edges {
                    node {
                        id
                        name
                        createdAt
                    }
                }
            }
        }';

        $result = $shopifyService->makeGraphQLRequest($query);

        if ($result && ($result['success'] ?? false)) {
            $locations = $result['data']['locations']['edges'] ?? [];
            $this->info("Found " . count($locations) . " locations:");

            foreach ($locations as $edge) {
                $location = $edge['node'];
                $this->info("- {$location['name']} (ID: {$location['id']})");
                $this->line("  - Created At: {$location['createdAt']}");
            }
        } else {
            $this->error('Failed to fetch locations: ' . json_encode($result));
        }
    }

    /**
     * Publish tất cả sản phẩm lên Online Store
     */
    public function publishProducts()
    {
        $this->info('Đang publish sản phẩm lên Online Store...');
        
        try {
            // 1. Lấy publicationId của Online Store
            $publicationId = $this->getOnlineStorePublicationId();
            $this->info("Tìm thấy Online Store publicationId: {$publicationId}");
            
            // 2. Publish tất cả products
            $this->publishAllProducts($publicationId);
            
        } catch (\Exception $e) {
            $this->error('Lỗi: ' . $e->getMessage());
            \Log::error('Publish products error: ' . $e->getMessage());
        }
    }
    
    /**
     * Lấy publicationId của Online Store
     */
    private function getOnlineStorePublicationId(): string
    {
        $query = '
        query {
            publications(first: 20) {
                edges {
                    node {
                        id
                        name
                    }
                }
            }
        }';
        
        $result = $this->shopifyService->makeGraphQLRequest($query);
        
        if (!($result['success'] ?? false)) {
            throw new \Exception('Failed to fetch publications: ' . json_encode($result));
        }
        
        $publications = $result['data']['publications']['edges'] ?? [];
        foreach ($publications as $edge) {
            if ($edge['node']['name'] === 'Online Store') {
                return $edge['node']['id'];
            }
        }
        
        throw new \Exception('Không tìm thấy publication "Online Store"');
    }
    
    /**
     * Publish tất cả sản phẩm
     */
    private function publishAllProducts(string $publicationId)
    {
        $cursor = null;
        $total = 0;
        $published = 0;
        $skipped = 0;
        $failed = 0;
        
        do {
            $products = $this->getProductsPage($cursor, $publicationId);
            $edges = $products['edges'] ?? [];
            
            foreach ($edges as $edge) {
                $product = $edge['node'];
                $total++;
                
                // Kiểm tra đã publish chưa
                if ($this->isProductPublished($product)) {
                    $skipped++;
                    $this->info("[SKIP] {$product['handle']} đã publish");
                    continue;
                }
                
                // Publish sản phẩm lên Online Store
                $success = $this->publishProductToOnlineStore($product['id'], $publicationId);
                
                if ($success) {
                    $published++;
                    $this->info("[OK] {$product['handle']} publish xong");
                } else {
                    $failed++;
                    $this->error("[FAIL] {$product['handle']} publish thất bại");
                }
            }
            
            $pageInfo = $products['pageInfo'] ?? null;
            $cursor = $pageInfo['endCursor'] ?? null;
            
        } while (!empty($pageInfo['hasNextPage']));
        
        $this->info("\nKết quả: Tổng {$total} | OK {$published} | Skip {$skipped} | Fail {$failed}");
    }
    
    /**
     * Lấy một trang products
     */
    private function getProductsPage($cursor = null, string $publicationId)
    {
        $query = '
        query ($cursor: String, $publicationId: ID!) {
            products(first: 100, after: $cursor) {
                pageInfo {
                    hasNextPage
                    endCursor
                }
                edges {
                    node {
                        id
                        handle
                        publishedOnPublication(publicationId: $publicationId)
                    }
                }
            }
        }';
        
        $variables = ['publicationId' => $publicationId];
        if ($cursor) {
            $variables['cursor'] = $cursor;
        }
        
        $result = $this->shopifyService->makeGraphQLRequest($query, $variables);
        
        if (!($result['success'] ?? false)) {
            throw new \Exception('Failed to fetch products: ' . json_encode($result));
        }
        
        return $result['data']['products'] ?? [];
    }
    
    /**
     * Kiểm tra sản phẩm đã publish chưa
     */
    private function isProductPublished(array $product): bool
    {
        return (bool)($product['publishedOnPublication'] ?? false);
    }
    
    /**
     * Publish một sản phẩm lên Online Store
     */
    private function publishProductToOnlineStore(string $productId, string $publicationId): bool
    {
        $mutation = '
        mutation ($id: ID!, $publicationId: ID!) {
            publishablePublish(id: $id, input: { publicationId: $publicationId }) {
                userErrors {
                    field
                    message
                }
            }
        }';
        
        $result = $this->shopifyService->makeGraphQLRequest($mutation, [
            'id' => $productId,
            'publicationId' => $publicationId,
        ]);
        
        if (!($result['success'] ?? false)) {
            \Log::error('Publish product failed: ' . json_encode($result));
            return false;
        }
        
        $userErrors = $result['data']['publishablePublish']['userErrors'] ?? [];
        if (!empty($userErrors)) {
            \Log::error('Publish userErrors: ' . json_encode($userErrors));
        }
        return empty($userErrors);
    }
} 