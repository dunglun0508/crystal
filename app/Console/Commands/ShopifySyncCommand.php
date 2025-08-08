<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\Product;
use App\Jobs\SyncAllCategoriesToShopifyJob;
use App\Jobs\SyncAllProductsToShopifyJob;
use App\Jobs\SyncProductsByCategoryJob;

class ShopifySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:sync
                            {type : Type of sync (categories, products, category-products, update-existing)}
                            {--category= : Category code for category-products sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data to Shopify using background jobs';

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
                                    case 'category-products':
                            $this->syncCategoryProducts();
                            break;
                        case 'update-existing':
                            $this->updateExistingProducts();
                            break;
                        default:
                            $this->error('Invalid sync type. Use: categories, products, category-products, or update-existing');
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
            
            $this->info('✅ Job dispatched successfully!');
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
            
            $this->info('✅ Job dispatched successfully!');
            $this->info('Run: php artisan queue:work --queue=shopify-sync');
        }
    }

    /**
     * Sync products by category
     */
    private function syncCategoryProducts()
    {
        $categoryCode = $this->option('category');
        
        if (!$categoryCode) {
            $this->error('Please provide category code using --category option');
            return;
        }

        $category = Category::where('code', $categoryCode)->first();
        
        if (!$category) {
            $this->error("Category with code '{$categoryCode}' not found");
            return;
        }

        $totalProducts = Product::where('category_code', $categoryCode)->count();
        
        $this->info("Found {$totalProducts} products in category '{$category->title}'");
        
        if ($this->confirm("Do you want to proceed with syncing products from category '{$category->title}'?")) {
            SyncProductsByCategoryJob::dispatch($category)
                ->onQueue('shopify-sync');
            
            $this->info('✅ Job dispatched successfully!');
            $this->info('Run: php artisan queue:work --queue=shopify-sync');
        }
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
                \App\Jobs\UpdateExistingProductJob::dispatch($product)
                    ->onQueue('shopify-sync')
                    ->delay(now()->addSeconds(rand(1, 5))); // Random delay to avoid rate limits
            }
            
            $this->info('✅ Update jobs dispatched successfully!');
            $this->info('Run: php artisan queue:work --queue=shopify-sync');
        }
    }
} 