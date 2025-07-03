<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrawlController;

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

Route::get('/categories-crawler', [CrawlController::class, 'categoriesCrawler'])->name('crawler.categories');
Route::get('/products-crawler', [CrawlController::class, 'productsCrawler'])->name('crawler.products');
Route::get('/level2-products-crawler', [CrawlController::class, 'crawlLevel2Products'])->name('crawler.level2-products');
Route::get('/level4-products-crawler', [CrawlController::class, 'crawlLevel4Products'])->name('crawler.level4-products');
Route::get('/crawl', [CrawlController::class, 'crawl']);
Route::get('/crawl-products', [CrawlController::class, 'crawlProducts']);
Route::get('/daily-crawler', [CrawlController::class, 'dailyCrawler']);
Route::get('/test-categories', [CrawlController::class, 'categoriesCrawler']);
Route::get('/test-products', [CrawlController::class, 'crawlProducts']);
