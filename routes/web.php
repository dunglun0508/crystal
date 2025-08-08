<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrawlController;
use App\Http\Controllers\UploadDataController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Crawler routes
Route::get('/categories-crawler', [CrawlController::class, 'categoriesCrawler'])->name('crawler.categories');
Route::get('/products-crawler', [CrawlController::class, 'productsCrawler'])->name('crawler.products');
Route::get('/level2-products-crawler', [CrawlController::class, 'crawlLevel2Products'])->name('crawler.level2-products');
Route::get('/crawl-products', [CrawlController::class, 'crawlProducts']);
Route::get('/daily-crawler', [CrawlController::class, 'dailyCrawler']);
Route::get('/test-categories', [CrawlController::class, 'categoriesCrawler']);

// Shopify upload routes
Route::prefix('shopify')->group(function () {
    Route::get('/check-connection', [UploadDataController::class, 'checkShopifyConnection'])->name('shopify.check-connection');
    Route::post('/sync-category/{category}', [UploadDataController::class, 'syncCategoryToShopify'])->name('shopify.sync-category');
    Route::post('/sync-product/{product}', [UploadDataController::class, 'syncProductToShopify'])->name('shopify.sync-product');
    Route::get('/sync-all-categories', [UploadDataController::class, 'syncAllCategoriesToShopify'])->name('shopify.sync-all-categories');
    Route::get('/sync-all-products', [UploadDataController::class, 'syncAllProductsToShopify'])->name('shopify.sync-all-products');
    Route::get('/sync-products-by-category/{category}', [UploadDataController::class, 'syncProductsByCategory'])->name('shopify.sync-products-by-category');
    Route::get('/delete-all-products', [UploadDataController::class, 'deleteAllProducts'])->name('shopify.delete-all-products');
});
