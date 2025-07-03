<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;

class CrawlController extends Controller
{
    public function __construct()
    {
        // Constructor to ensure proper instantiation
    }

    private function getFinalRedirectUrl($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,      // Theo dõi redirect
            CURLOPT_RETURNTRANSFER => true,      // Không in ra màn hình
            CURLOPT_HEADER => true,              // Lấy cả header
            CURLOPT_NOBODY => true,              // Không cần nội dung trang
        ]);
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);
        return $finalUrl;
    }

    private function fixImageUrl($imageUrl) {
        if (str_starts_with($imageUrl, 'http')) {
            return $imageUrl;
        }
        
        $imageUrl = ltrim($imageUrl, '/');
        
        if (str_starts_with($imageUrl, 'resize/')) {
            return 'https://www.artcrystal.eu/' . $imageUrl;
        }
        
        return 'https://www.artcrystal.eu/' . $imageUrl;
    }
    
    private function createStreamContext($timeout = 30) {
        return stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'timeout' => $timeout
            ]
        ]);
    }

    public function categoriesCrawler(Request $request){
        \Log::info("=== CATEGORIES CRAWL START ===");
        
        include 'simple_html_dom.php';
        $url = 'https://www.artcrystal.eu/';
        $html = file_get_html($url);
        if (!$html) {
            \Log::error("CategoriesCrawler: Failed to load URL");
            return response()->json(['error' => 'Failed to load URL']);
        }
        
        $categories = [];
        $id = 1;
        $crawledCount = 0;
        $levelCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        
        // Find main menu items
        $menuItems = $html->find('.s1-sub-group .s1-submenu-item.level-1');
        
        foreach ($menuItems as $item) {
            $link = $item->find('a.s1-submenu-link', 0);
            if ($link) {
                $title = trim($link->plaintext);
                $href = $link->href;
                
                if ($title && $href && !str_contains($href, 'javascript:') && !str_contains($href, '#')) {
                    $categoryCode = $href . '_' . 1;
                    $categories[] = [
                        'id' => $id++,
                        'title' => $title,
                        'slug' => $href,
                        'parent_id' => null,
                        'level' => 1,
                        'code' => $categoryCode
                    ];
                    $crawledCount++;
                    $levelCounts[1]++;
                    
                    // Parse submenu recursively
                    $submenu = $item->find('ul.s1-submenu-items.level-2 li.s1-submenu-item.level-2');
                    if (!empty($submenu)) {
                        $this->parseMenuRecursive($submenu, $href, $id, $href, 2, $categories, $crawledCount, $levelCounts);
                    }
                }
            }
        }
        
        // Database operations
        $insertCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        
        // Get existing categories count before operations
        $existingCount = Category::count();
        
        foreach ($categories as $cat) {
            $existingCategory = Category::where('slug', $cat['slug'])->first();
            
            if ($existingCategory) {
                $existingCategory->update([
                    'title' => $cat['title'],
                    'parent_id' => $cat['parent_id'],
                    'level' => $cat['level'],
                    'code' => $cat['code']
                ]);
                $updateCount++;
            } else {
                Category::create([
                    'title' => $cat['title'],
                    'slug' => $cat['slug'],
                    'parent_id' => $cat['parent_id'],
                    'level' => $cat['level'],
                    'code' => $cat['code']
                ]);
                $insertCount++;
            }
        }
        
        // Get final count
        $finalCount = Category::count();
        
        \Log::info("    Crawled: {$crawledCount} categories");
        \Log::info("    Level 1: {$levelCounts[1]}, Level 2: {$levelCounts[2]}, Level 3: {$levelCounts[3]}");
        \Log::info("    DB Before: {$existingCount}, After: {$finalCount}");
        \Log::info("    DB Operations: Inserted: {$insertCount}, Updated: {$updateCount}");
        \Log::info("=== CATEGORIES CRAWL COMPLETE ===");
        
        return response()->json([
            'success' => true, 
            'categories' => $categories,
            'summary' => [
                'crawled' => $crawledCount,
                'level_counts' => $levelCounts,
                'db_before' => $existingCount,
                'db_after' => $finalCount,
                'inserted' => $insertCount,
                'updated' => $updateCount
            ]
        ]);
    }

    private function parseMenuRecursive($elements, $url, &$id, $parentSlug, $level, &$categories, &$crawledCount, &$levelCounts) {
        foreach ($elements as $item) {
            $link = $item->find('a.s1-submenu-link', 0);
            if ($link) {
                $title = trim($link->plaintext);
                $href = $link->href;
                
                if ($title && $href && !str_contains($href, 'javascript:') && !str_contains($href, '#')) {
                    $categoryCode = $href . '_' . $level;
                    $categories[] = [
                        'id' => $id++,
                        'title' => $title,
                        'slug' => $href,
                        'parent_id' => $parentSlug,
                        'level' => $level,
                        'code' => $categoryCode
                    ];
                    $crawledCount++;
                    $levelCounts[$level]++;
                    
                    // Parse sub-submenu recursively
                    $submenu = $item->find('ul.s1-submenu-items li.s1-submenu-item.level-3');
                    if (!empty($submenu)) {
                        $this->parseMenuRecursive($submenu, $href, $id, $href, $level + 1, $categories, $crawledCount, $levelCounts);
                    }
                }
            }
        }
    }

    public function productsCrawler(Request $request){
        $this->crawlProducts();
        return response()->json(['success' => true, 'message' => 'Products crawling completed']);
    }

    public function crawlLevel2Products(Request $request){
        $this->crawlLevel2ProductsInternal();
        return response()->json(['success' => true, 'message' => 'Level 2 products crawling completed']);
    }

    public function dailyCrawler(Request $request) {
        // Crawl categories first
        $this->categoriesCrawler($request);
        
        // Then crawl products
        $this->crawlProducts();
        
        // Then crawl level 2 products
        $this->crawlLevel2ProductsInternal();
        
        // Then crawl level 4 products
        $this->crawlLevel4ProductsInternal();
        
        // Finally crawl product details
        $this->crawlProductDetails();
        
        return response()->json(['success' => true, 'message' => 'Daily crawling completed']);
    }

    private function crawlProducts() {
        \Log::info("=== LEVEL 3 PRODUCTS CRAWL START ===");
        
        $categories = Category::where('level', 3)->get();
        $totalCategories = $categories->count();
        
        \Log::info("Starting crawlProducts for {$totalCategories} level 3 categories");
        
        foreach ($categories as $index => $cat) {
            $progress = round(($index + 1) / $totalCategories * 100, 1);
            \Log::info("crawlProducts: Processing category " . ($index + 1) . "/{$totalCategories}: {$cat->slug} (level {$cat->level}) ({$progress}%)");
            
            $baseUrl = 'https://www.artcrystal.eu';
            $products = $this->crawlProductsByCategorySlug($baseUrl . $cat->slug, $cat->code);
            
            if (!empty($products)) {
                foreach ($products as $productData) {
                    // Clean data before upsert
                    $productData['price'] = $productData['price'] ?: null;
                    $productData['image'] = $productData['image'] ?: 'default-product.jpg';
                    
                    Product::updateOrCreate(
                        ['code' => $productData['code']],
                        $productData
                    );
                }
                
                \Log::info("crawlProducts: Category {$cat->slug} - " . count($products) . " products processed");
            } else {
                \Log::warning("crawlProducts: No products found for category {$cat->slug}");
            }
        }
        
        \Log::info("crawlProducts: Completed for all level 3 categories");
    }

    public function crawlProductsByCategorySlug($url, $categoryCode) {
        if (!function_exists('file_get_html')) {
            include app_path('Http/Controllers/simple_html_dom.php');
        }
        
        $context = $this->createStreamContext(30);
        
        $allProducts = [];
        $page = 1;
        $maxPages = 20; // Reasonable limit for most categories
        $currentUrl = $url;
        $redirectedUrl = null; // Store the actual URL after redirect
        
        while ($page <= $maxPages) {
            \Log::info("Crawling page {$page}: {$currentUrl}");
            
            $html = file_get_html($currentUrl, false, $context);
            if (!$html) {
                \Log::warning("crawlProductsByCategorySlug: Failed to load URL: {$currentUrl}");
                break;
            }
            
            // On first page, get the actual URL after redirect
            if ($page === 1) {
                // Use the new function to get the final URL after redirects
                $finalUrl = $this->getFinalRedirectUrl($currentUrl);
                if ($finalUrl && $finalUrl !== $currentUrl) {
                    $redirectedUrl = $finalUrl;
                    \Log::info("Detected redirected URL: {$redirectedUrl}");
                } else {
                    // Fallback: use the original URL
                    $redirectedUrl = $currentUrl;
                    \Log::info("No redirect detected, using original URL: {$redirectedUrl}");
                }
            }
            
            $products = [];
            
            // Check for product list (ul.productListFGrid li.s1-gridItem.s1-itemBuyable) - Level 3 products
            $productItems = $html->find('ul.productListFGrid li.s1-gridItem.s1-itemBuyable');
            
            // Note: div.directoryList a.directoryListItem are subcategories (level 4), not products
            // We'll handle them separately if needed
            
            if (empty($productItems)) {
                \Log::info("No more products found on page {$page}, stopping pagination");
                break;
            }
            
            \Log::info("Found " . count($productItems) . " grid products on page {$page}");
            
            // Process grid-style products (existing logic)
            foreach ($productItems as $item) {
                $title = trim($item->find('h3.s1-listProductTitle a', 0)?->plaintext ?? '');
                $priceRaw = trim($item->find('div.s1-gridItem-priceCont p.price span', 0)?->plaintext ?? '');
                preg_match('/([\d\.,]+)\s*([\p{Sc}A-Za-z]+)/u', $priceRaw, $matches);
                $price_number = isset($matches[1]) ? str_replace([','], ['.'], str_replace('.', '', $matches[1])) : '';
                $price_currency = isset($matches[2]) ? $matches[2] : '';
                
                $imgTag = $item->find('img.s1-mainImg', 0);
                $imageUrl = $imgTag?->getAttribute('data-src') ?? $imgTag?->src ?? '';
                
                $image = '';
                if ($imageUrl) {
                    $fullUrl = $this->fixImageUrl($imageUrl);
                    
                    $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                    $savePath = public_path('images/products/' . $filename);
                    if (!file_exists($savePath)) {
                        if (!file_exists(public_path('images/products'))) {
                            mkdir(public_path('images/products'), 0777, true);
                        }
                        
                        $context = $this->createStreamContext(10);
                        try {
                            $imageContent = file_get_contents($fullUrl, false, $context);
                            if ($imageContent !== false) {
                                file_put_contents($savePath, $imageContent);
                                $image = 'images/products/' . $filename;
                                \Log::info("Successfully downloaded image: {$filename}");
                            } else {
                                \Log::warning("Failed to download image: {$fullUrl}");
                                $image = 'default-product.jpg';
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Error downloading image {$fullUrl}: " . $e->getMessage());
                            $image = 'default-product.jpg';
                        }
                    } else {
                        $image = 'images/products/' . $filename;
                    }
                } else {
                    $image = 'default-product.jpg';
                }
                
                $urlProduct = $item->find('h3.s1-listProductTitle a', 0)?->href ?? '';
                
                // Clean the slug - extract only the path part
                $slug = $urlProduct;
                if (str_starts_with($slug, 'http')) {
                    $parsedUrl = parse_url($slug);
                    $slug = $parsedUrl['path'] ?? $slug;
                }
                
                $discount = '';
                $discountTag = $item->find('span.s1-discountBedge-value', 0);
                if ($discountTag) {
                    $discount = trim($discountTag->plaintext);
                }
                
                $indicators = [];
                $indicatorSpans = $item->find('p.indicators span.indicator');
                foreach ($indicatorSpans as $span) {
                    $indicators[] = trim($span->plaintext);
                }
                $indicatorsStr = implode(', ', $indicators);
                
                // Generate product code for level 3 - slug + category code
                $productCode = $this->generateProductCode($urlProduct) . '_' . $categoryCode;
                
                $products[] = [
                    'code' => $productCode,
                    'title' => $title,
                    'price' => $price_number,
                    'currency' => $price_currency,
                    'image' => $image,
                    'slug' => $slug,
                    'discount' => $discount,
                    'indicators' => $indicatorsStr,
                    'category_code' => $categoryCode
                ];
            }
            
            $allProducts = array_merge($allProducts, $products);
            
            // Check if there's a next page - simplified pagination detection
            $nextPageLink = $html->find('li.paginationItem a.buttonPaginationNext', 0);
            
            if ($nextPageLink) {
                \Log::info("Found pagination link: li.paginationItem a.buttonPaginationNext");
            } else {
                \Log::info("No pagination link found, stopping at page {$page}");
            }
            
            // Stop if no products found on current page OR no next page link
            if (empty($products)) {
                \Log::info("No products found on page {$page}, stopping pagination");
                break;
            }
            
            if (!$nextPageLink) {
                \Log::info("No valid next page link found, stopping at page {$page}");
                break;
            }
            
            $page++;
            
            // Build next page URL using the redirected URL as base
            $nextPageHref = $nextPageLink->href;
            \Log::info("Next page href: {$nextPageHref}");
            
            if ($redirectedUrl) {
                // Use the redirected URL as the base
                if (str_starts_with($nextPageHref, 'http')) {
                    // Full URL provided
                    $currentUrl = $nextPageHref;
                } else {
                    // Relative URL - combine with redirected URL
                    $parsedRedirected = parse_url($redirectedUrl);
                    $baseUrl = $parsedRedirected['scheme'] . '://' . $parsedRedirected['host'] . $parsedRedirected['path'];
                    
                    if (str_starts_with($nextPageHref, '?')) {
                        // Query parameter - append to base URL
                        $currentUrl = $baseUrl . $nextPageHref;
                    } else {
                        // Path - replace the path
                        $currentUrl = $baseUrl . '/' . ltrim($nextPageHref, '/');
                    }
                }
            } else {
                // Fallback to original logic
                $currentUrl = $currentUrl . $nextPageHref;
            }
            
            \Log::info("Next page URL: {$currentUrl}");
        }
        
        \Log::info("Total products crawled across all pages: " . count($allProducts));
        return $allProducts;
    }

    private function generateProductCode($url) {
        // Extract product identifier from URL
        $urlParts = explode('/', trim($url, '/'));
        
        // Find the part that looks like a product slug (contains letters and numbers)
        $productSlug = '';
        foreach ($urlParts as $part) {
            if (preg_match('/[a-zA-Z]/', $part) && preg_match('/[0-9]/', $part)) {
                $productSlug = $part;
                break;
            }
        }
        
        // If no suitable slug found, use the last part
        if (empty($productSlug)) {
            $productSlug = end($urlParts);
        }
        
        // Clean the product slug - keep only alphanumeric, hyphens, and underscores
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $productSlug);
    }

    public function crawlProductDetail($url, $slug = null) {
        if (!function_exists('file_get_html')) {
            include app_path('Http/Controllers/simple_html_dom.php');
        }
        $baseUrl = 'https://www.artcrystal.eu';
        $fullUrl = str_starts_with($url, 'http') ? $url : $baseUrl . $url;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        // Check HTTP status before loading HTML
        $headers = get_headers($fullUrl, 1);
        if ($headers === false || strpos($headers[0], '200') === false) {
            \Log::warning("crawlProductDetail: HTTP Error - {$fullUrl} - Status: " . ($headers[0] ?? 'Unknown'));
            return [
                'gallery' => json_encode([]),
                'gallery_local' => json_encode([]),
                'detail_indicators' => '',
                'meta_description' => '',
                'long_description' => '',
                'specs' => json_encode([]),
                'key_features' => json_encode([])
            ];
        }
        
        $html = file_get_html($fullUrl, false, $context);
        if (!$html) {
            \Log::warning("crawlProductDetail: Failed to load URL: {$fullUrl}");
            return [
                'gallery' => json_encode([]),
                'gallery_local' => json_encode([]),
                'detail_indicators' => '',
                'meta_description' => '',
                'long_description' => '',
                'specs' => json_encode([]),
                'key_features' => json_encode([])
            ];
        }
        
        // Extract gallery images
        $gallery = [];
        $galleryLocal = [];
        foreach ($html->find('div.s1-detailGallery figure.galleryItem') as $fig) {
            $img = $fig->getAttribute('data-full') ?? '';
            if ($img) {
                $fullImageUrl = $this->fixImageUrl($img);
                
                if (filter_var($fullImageUrl, FILTER_VALIDATE_URL)) {
                    $filename = basename(parse_url($img, PHP_URL_PATH));
                    $savePath = public_path('images/products/' . $filename);
                    if (!file_exists($savePath)) {
                        if (!file_exists(public_path('images/products'))) {
                            mkdir(public_path('images/products'), 0777, true);
                        }
                        
                        $context = $this->createStreamContext(10);
                        try {
                            $imageContent = file_get_contents($fullImageUrl, false, $context);
                            if ($imageContent !== false) {
                                file_put_contents($savePath, $imageContent);
                                $gallery[] = $fullImageUrl;
                                $galleryLocal[] = 'images/products/' . $filename;
                                \Log::info("Successfully downloaded image: {$filename}");
                            } else {
                                \Log::warning("Failed to download image: {$fullImageUrl}");
                                $image = 'default-product.jpg';
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Error downloading image {$fullImageUrl}: " . $e->getMessage());
                            $image = 'default-product.jpg';
                        }
                    } else {
                        $gallery[] = $fullImageUrl;
                        $galleryLocal[] = 'images/products/' . $filename;
                    }
                } else {
                    \Log::warning("Invalid image URL: {$img}");
                }
            }
        }
        
        // Extract detail indicators
        $indicators = [];
        foreach ($html->find('div.s1-detailImgOuter p.indicators span.indicator') as $span) {
            $indicators[] = trim($span->plaintext);
        }
        $indicatorsStr = implode(', ', $indicators);
        
        // Extract meta description
        $metaDescription = $html->find('meta[itemprop=description]', 0)?->content ?? '';
        
        // Extract long description from div.ac-product__long-cont
        $longDesc = '';
        $longDescDiv = $html->find('div.ac-product__long-cont div.userHTMLContent', 0);
        if ($longDescDiv) {
            $longDesc = trim($longDescDiv->innertext());
        }
        
        // Extract specifications from table
        $specs = [];
        foreach ($html->find('table.tabAdditionalInfo tr') as $tr) {
            $key = trim($tr->find('td.tabAdditionalInfoTitle', 0)?->plaintext ?? '');
            $val = trim($tr->find('td', 1)?->plaintext ?? '');
            if ($key && $val) {
                $specs[$key] = $val;
            }
        }
        
        // Extract key features - look for the correct structure
        $features = [];
        // Look for key features in different possible locations
        $featuresDiv = $html->find('div.ac-product__key-features', 0);
        if ($featuresDiv) {
            // Try to find list items in the key features section
            foreach ($html->find('div.ac-product__key-features ul li') as $li) {
                $features[] = trim($li->plaintext);
            }
        }
        
        // If no features found in key features section, try other locations
        if (empty($features)) {
            // Look for features in highlights section
            foreach ($html->find('ul.highlightsList li.highlightsItem strong.highlightsFigTitle') as $title) {
                $features[] = trim($title->plaintext);
            }
        }
        
        // Extract variant information from s1-buttonRows-cont
        $variants = [];
        foreach ($html->find('div.s1-buttonRows-cont div.s1-buttonRow') as $row) {
            $variant = [];
            
            // Extract variant name
            $variantName = $row->find('span.s1-buttonRow-txt', 0);
            if ($variantName) {
                $variant['name'] = trim($variantName->plaintext);
            }
            
            // Extract Art.No.
            $artNo = $row->find('p.s1-buttonRow-line span.s1-buttonRow-txt', 0);
            if ($artNo) {
                $variant['art_no'] = trim($artNo->plaintext);
            }
            
            // Extract price
            $priceSpan = $row->find('p.s1-buttonRow-line span.s1-buttonRow-price', 0);
            if ($priceSpan) {
                $variant['price'] = trim($priceSpan->plaintext);
            }
            
            if (!empty($variant)) {
                $variants[] = $variant;
            }
        }
        
        return [
            'gallery' => json_encode($gallery),
            'gallery_local' => json_encode($galleryLocal),
            'detail_indicators' => $indicatorsStr,
            'meta_description' => $metaDescription,
            'long_description' => $longDesc,
            'specs' => json_encode($specs),
            'key_features' => json_encode($features),
            'variants' => json_encode($variants)
        ];
    }

    // Public wrapper method for Jobs to call
    public function crawlProductDetailForJob($url, $slug = null) {
        return $this->crawlProductDetail($url, $slug);
    }

    public function crawlLevel2ProductsByCategorySlug($url, $categoryCode) {
        if (!function_exists('file_get_html')) {
            include app_path('Http/Controllers/simple_html_dom.php');
        }
        
        $context = $this->createStreamContext(30);
        
        $allProducts = [];
        $page = 1;
        $maxPages = 20; // Reasonable limit for most categories
        $currentUrl = $url;
        $redirectedUrl = null; // Store the actual URL after redirect
        
        while ($page <= $maxPages) {
            \Log::info("Crawling Level 2 page {$page}: {$currentUrl}");
            
            $html = file_get_html($currentUrl, false, $context);
            if (!$html) {
                \Log::warning("crawlLevel2ProductsByCategorySlug: Failed to load URL: {$currentUrl}");
                break;
            }
            
            // On first page, get the actual URL after redirect
            if ($page === 1) {
                // Use the new function to get the final URL after redirects
                $finalUrl = $this->getFinalRedirectUrl($currentUrl);
                if ($finalUrl && $finalUrl !== $currentUrl) {
                    $redirectedUrl = $finalUrl;
                    \Log::info("Detected redirected URL: {$redirectedUrl}");
                } else {
                    // Fallback: use the original URL
                    $redirectedUrl = $currentUrl;
                    \Log::info("No redirect detected, using original URL: {$redirectedUrl}");
                }
            }
            
            $products = [];
            
            // Check for product list (ul.productListFGrid li.s1-gridItem.s1-itemBuyable) - Level 2 products
            $productItems = $html->find('ul.productListFGrid li.s1-gridItem.s1-itemBuyable');
            
            // Note: div.directoryList a.directoryListItem are subcategories, not products
            // We'll handle them separately if needed
            
            if (empty($productItems)) {
                \Log::info("No more Level 2 products found on page {$page}, stopping pagination");
                break;
            }
            
            \Log::info("Found " . count($productItems) . " grid products on page {$page}");
            
            // Process grid-style products (existing logic)
            foreach ($productItems as $item) {
                $title = trim($item->find('h3.s1-listProductTitle a', 0)?->plaintext ?? '');
                $priceRaw = trim($item->find('div.s1-gridItem-priceCont p.price span', 0)?->plaintext ?? '');
                preg_match('/([\d\.,]+)\s*([\p{Sc}A-Za-z]+)/u', $priceRaw, $matches);
                $price_number = isset($matches[1]) ? str_replace([','], ['.'], str_replace('.', '', $matches[1])) : '';
                $price_currency = isset($matches[2]) ? $matches[2] : '';
                
                $imgTag = $item->find('img.s1-mainImg', 0);
                $imageUrl = $imgTag?->getAttribute('data-src') ?? $imgTag?->src ?? '';
                
                $image = '';
                if ($imageUrl) {
                    $fullUrl = $this->fixImageUrl($imageUrl);
                    
                    $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                    $savePath = public_path('images/products/' . $filename);
                    if (!file_exists($savePath)) {
                        if (!file_exists(public_path('images/products'))) {
                            mkdir(public_path('images/products'), 0777, true);
                        }
                        
                        $context = $this->createStreamContext(10);
                        try {
                            $imageContent = file_get_contents($fullUrl, false, $context);
                            if ($imageContent !== false) {
                                file_put_contents($savePath, $imageContent);
                                $image = 'images/products/' . $filename;
                                \Log::info("Successfully downloaded image: {$filename}");
                            } else {
                                \Log::warning("Failed to download image: {$fullUrl}");
                                $image = 'default-product.jpg';
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Error downloading image {$fullUrl}: " . $e->getMessage());
                            $image = 'default-product.jpg';
                        }
                    } else {
                        $image = 'images/products/' . $filename;
                    }
                } else {
                    $image = 'default-product.jpg';
                }
                
                $urlProduct = $item->find('h3.s1-listProductTitle a', 0)?->href ?? '';
                
                // Clean the slug - extract only the path part
                $slug = $urlProduct;
                if (str_starts_with($slug, 'http')) {
                    $parsedUrl = parse_url($slug);
                    $slug = $parsedUrl['path'] ?? $slug;
                }
                
                $discount = '';
                $discountTag = $item->find('span.s1-discountBedge-value', 0);
                if ($discountTag) {
                    $discount = trim($discountTag->plaintext);
                }
                
                $indicators = [];
                $indicatorSpans = $item->find('p.indicators span.indicator');
                foreach ($indicatorSpans as $span) {
                    $indicators[] = trim($span->plaintext);
                }
                $indicatorsStr = implode(', ', $indicators);
                
                // Generate product code for level 2 - slug + category code
                $productCode = $this->generateProductCode($urlProduct) . '_' . $categoryCode;
                
                $products[] = [
                    'code' => $productCode,
                    'title' => $title,
                    'price' => $price_number,
                    'currency' => $price_currency,
                    'image' => $image,
                    'slug' => $slug,
                    'discount' => $discount,
                    'indicators' => $indicatorsStr,
                    'category_code' => $categoryCode
                ];
            }
            
            $allProducts = array_merge($allProducts, $products);
            
            // Check if there's a next page - simplified pagination detection
            $nextPageLink = $html->find('li.paginationItem a.buttonPaginationNext', 0);
            
            if ($nextPageLink) {
                \Log::info("Found pagination link: li.paginationItem a.buttonPaginationNext");
            } else {
                \Log::info("No pagination link found, stopping at page {$page}");
            }
            
            // Stop if no products found on current page OR no next page link
            if (empty($products)) {
                \Log::info("No products found on page {$page}, stopping pagination");
                break;
            }
            
            if (!$nextPageLink) {
                \Log::info("No valid next page link found, stopping at page {$page}");
                break;
            }
            
            $page++;
            
            // Build next page URL using the redirected URL as base
            $nextPageHref = $nextPageLink->href;
            \Log::info("Next page href: {$nextPageHref}");
            
            if ($redirectedUrl) {
                // Use the redirected URL as the base
                if (str_starts_with($nextPageHref, 'http')) {
                    // Full URL provided
                    $currentUrl = $nextPageHref;
                } else {
                    // Relative URL - combine with redirected URL
                    $parsedRedirected = parse_url($redirectedUrl);
                    $baseUrl = $parsedRedirected['scheme'] . '://' . $parsedRedirected['host'] . $parsedRedirected['path'];
                    
                    if (str_starts_with($nextPageHref, '?')) {
                        // Query parameter - append to base URL
                        $currentUrl = $baseUrl . $nextPageHref;
                    } else {
                        // Path - replace the path
                        $currentUrl = $baseUrl . '/' . ltrim($nextPageHref, '/');
                    }
                }
            } else {
                // Fallback to original logic
                $currentUrl = $currentUrl . $nextPageHref;
            }
            
            \Log::info("Next page URL: {$currentUrl}");
        }
        
        \Log::info("Total Level 2 products crawled across all pages: " . count($allProducts));
        return $allProducts;
    }

    private function crawlLevel2ProductsInternal() {
        $categories = Category::where('level', 2)->get();
        $totalCategories = $categories->count();
        
        \Log::info("Starting crawlLevel2ProductsInternal for {$totalCategories} level 2 categories");
        
        foreach ($categories as $index => $cat) {
            $progress = round(($index + 1) / $totalCategories * 100, 1);
            \Log::info("crawlLevel2ProductsInternal: Processing category " . ($index + 1) . "/{$totalCategories}: {$cat->slug} (level {$cat->level}) ({$progress}%)");
            
            $baseUrl = 'https://www.artcrystal.eu';
            $products = $this->crawlLevel2ProductsByCategorySlug($baseUrl . $cat->slug, $cat->code);
            
            if (!empty($products)) {
                foreach ($products as $productData) {
                    // Clean data before upsert
                    $productData['price'] = $productData['price'] ?: null;
                    $productData['image'] = $productData['image'] ?: 'default-product.jpg';
                    
                    Product::updateOrCreate(
                        ['code' => $productData['code']],
                        $productData
                    );
                }
                
                \Log::info("crawlLevel2ProductsInternal: Category {$cat->slug} - " . count($products) . " products processed");
            } else {
                \Log::warning("crawlLevel2ProductsInternal: No products found for category {$cat->slug}");
            }
        }
        
        \Log::info("crawlLevel2ProductsInternal: Completed for all level 2 categories");
    }

    public function crawlProductDetails() {
        // Query distinct slugs from products table
        $distinctSlugs = Product::select('slug')->distinct()->get();
        $totalSlugs = $distinctSlugs->count();
        
        \Log::info("Starting crawlProductDetails for {$totalSlugs} distinct slugs");
        
        foreach ($distinctSlugs as $index => $slugData) {
            $progress = round(($index + 1) / $totalSlugs * 100, 1);
            $slug = $slugData->slug;
            \Log::info("crawlProductDetails: Processing slug " . ($index + 1) . "/{$totalSlugs}: {$slug} ({$progress}%)");
            
            $baseUrl = 'https://www.artcrystal.eu';
            $fullUrl = $baseUrl . $slug;
            
            $productDetail = $this->crawlProductDetail($fullUrl, $slug);
            
            // Get the product code for this slug
            $product = Product::where('slug', $slug)->first();
            if (!$product) {
                \Log::warning("crawlProductDetails: No product found for slug {$slug}");
                continue;
            }
            
            // Save product detail
            $existingDetail = ProductDetail::where('code', $product->code)->first();
            
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
                    'code' => $product->code,
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
                        $existingVariant = ProductVariant::where('code', $slug)
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
                                'code' => $slug,
                                'name' => $variant['name'] ?? '',
                                'art_no' => $variant['art_no'] ?? '',
                                'price' => $price
                            ]);
                        }
                    }
                }
            }
        }
        
        \Log::info("crawlProductDetails: Completed for all distinct slugs");
    }

    public function crawlLevel4Products(Request $request){
        $this->crawlLevel4ProductsInternal();
        return response()->json(['success' => true, 'message' => 'Level 4 products crawling completed']);
    }

    private function crawlLevel4ProductsInternal() {
        // First, get all level 3 categories
        $level3Categories = Category::where('level', 3)->get();
        $totalLevel3Categories = $level3Categories->count();
        
        \Log::info("Starting crawlLevel4ProductsInternal for {$totalLevel3Categories} level 3 categories");
        
        $level4Categories = [];
        
        // Find level 4 categories from level 3 categories
        foreach ($level3Categories as $index => $level3Cat) {
            $progress = round(($index + 1) / $totalLevel3Categories * 100, 1);
            \Log::info("crawlLevel4ProductsInternal: Finding level 4 categories from level 3 category " . ($index + 1) . "/{$totalLevel3Categories}: {$level3Cat->slug} ({$progress}%)");
            
            $baseUrl = 'https://www.artcrystal.eu';
            $level4Subcategories = $this->findLevel4Categories($baseUrl . $level3Cat->slug, $level3Cat->code);
            
            if (!empty($level4Subcategories)) {
                $level4Categories = array_merge($level4Categories, $level4Subcategories);
                \Log::info("crawlLevel4ProductsInternal: Found " . count($level4Subcategories) . " level 4 categories from {$level3Cat->slug}");
            }
        }
        
        // Save level 4 categories to database
        foreach ($level4Categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'title' => $cat['title'],

                    'parent_id' => $cat['parent_id'],
                    'level' => $cat['level'],
                    'code' => $cat['code']
                ]
            );
        }
        
        \Log::info("crawlLevel4ProductsInternal: Found and saved " . count($level4Categories) . " level 4 categories");
        
        // Now crawl products for level 4 categories
        $level4CategoriesFromDB = Category::where('level', 4)->get();
        $totalLevel4Categories = $level4CategoriesFromDB->count();
        
        \Log::info("Starting product crawling for {$totalLevel4Categories} level 4 categories");
        
        foreach ($level4CategoriesFromDB as $index => $cat) {
            $progress = round(($index + 1) / $totalLevel4Categories * 100, 1);
            \Log::info("crawlLevel4ProductsInternal: Processing level 4 category " . ($index + 1) . "/{$totalLevel4Categories}: {$cat->slug} (level {$cat->level}) ({$progress}%)");
            
            $baseUrl = 'https://www.artcrystal.eu';
            $products = $this->crawlLevel4ProductsByCategorySlug($baseUrl . $cat->slug, $cat->code);
            
            if (!empty($products)) {
                foreach ($products as $productData) {
                    // Clean data before upsert
                    $productData['price'] = $productData['price'] ?: null;
                    $productData['image'] = $productData['image'] ?: 'default-product.jpg';
                    
                    Product::updateOrCreate(
                        ['code' => $productData['code']],
                        $productData
                    );
                }
                
                \Log::info("crawlLevel4ProductsInternal: Level 4 category {$cat->slug} - " . count($products) . " products processed");
            } else {
                \Log::warning("crawlLevel4ProductsInternal: No products found for level 4 category {$cat->slug}");
            }
        }
        
        \Log::info("crawlLevel4ProductsInternal: Completed for all level 4 categories");
    }

    private function findLevel4Categories($url, $parentCategoryCode) {
        if (!function_exists('file_get_html')) {
            include app_path('Http/Controllers/simple_html_dom.php');
        }
        
        $context = $this->createStreamContext(30);
        
        $html = file_get_html($url, false, $context);
        if (!$html) {
            \Log::warning("findLevel4Categories: Failed to load URL: {$url}");
            return [];
        }
        
        $level4Categories = [];
        
        // Find level 4 categories from div.directoryList a.directoryListItem
        $directoryItems = $html->find('div.directoryList a.directoryListItem');
        
        foreach ($directoryItems as $item) {
            $title = trim($item->find('span.directoryListLink', 0)?->plaintext ?? '');
            $href = $item->href ?? '';
            
            if ($title && $href && !str_contains($href, 'javascript:') && !str_contains($href, '#')) {
                // Clean the slug - extract only the path part
                $slug = $href;
                if (str_starts_with($slug, 'http')) {
                    $parsedUrl = parse_url($slug);
                    $slug = $parsedUrl['path'] ?? $slug;
                }
                
                $level4Categories[] = [
                    'title' => $title,
                    'slug' => $slug,
                    'parent_id' => $parentCategoryCode,
                    'level' => 4,
                    'code' => $slug . '_' . 4
                ];
            }
        }
        
        return $level4Categories;
    }

    public function crawlLevel4ProductsByCategorySlug($url, $categoryCode) {
        if (!function_exists('file_get_html')) {
            include app_path('Http/Controllers/simple_html_dom.php');
        }
        
        $context = $this->createStreamContext(30);
        
        $allProducts = [];
        $page = 1;
        $maxPages = 20; // Reasonable limit for most categories
        $currentUrl = $url;
        $redirectedUrl = null; // Store the actual URL after redirect
        
        while ($page <= $maxPages) {
            \Log::info("Crawling Level 4 page {$page}: {$currentUrl}");
            
            $html = file_get_html($currentUrl, false, $context);
            if (!$html) {
                \Log::warning("crawlLevel4ProductsByCategorySlug: Failed to load URL: {$currentUrl}");
                break;
            }
            
            // On first page, get the actual URL after redirect
            if ($page === 1) {
                // Use the new function to get the final URL after redirects
                $finalUrl = $this->getFinalRedirectUrl($currentUrl);
                if ($finalUrl && $finalUrl !== $currentUrl) {
                    $redirectedUrl = $finalUrl;
                    \Log::info("Detected redirected URL: {$redirectedUrl}");
                } else {
                    // Fallback: use the original URL
                    $redirectedUrl = $currentUrl;
                    \Log::info("No redirect detected, using original URL: {$redirectedUrl}");
                }
            }
            
            $products = [];
            
            // Check for product list (ul.productListFGrid li.s1-gridItem.s1-itemBuyable) - Level 4 products
            $productItems = $html->find('ul.productListFGrid li.s1-gridItem.s1-itemBuyable');
            
            // Note: div.directoryList a.directoryListItem are subcategories, not products
            // We'll handle them separately if needed
            
            if (empty($productItems)) {
                \Log::info("No more Level 4 products found on page {$page}, stopping pagination");
                break;
            }
            
            \Log::info("Found " . count($productItems) . " grid products on page {$page}");
            
            // Process grid-style products (existing logic)
            foreach ($productItems as $item) {
                $title = trim($item->find('h3.s1-listProductTitle a', 0)?->plaintext ?? '');
                $priceRaw = trim($item->find('div.s1-gridItem-priceCont p.price span', 0)?->plaintext ?? '');
                preg_match('/([\d\.,]+)\s*([\p{Sc}A-Za-z]+)/u', $priceRaw, $matches);
                $price_number = isset($matches[1]) ? str_replace([','], ['.'], str_replace('.', '', $matches[1])) : '';
                $price_currency = isset($matches[2]) ? $matches[2] : '';
                
                $imgTag = $item->find('img.s1-mainImg', 0);
                $imageUrl = $imgTag?->getAttribute('data-src') ?? $imgTag?->src ?? '';
                
                $image = '';
                if ($imageUrl) {
                    $fullUrl = $this->fixImageUrl($imageUrl);
                    
                    $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                    $savePath = public_path('images/products/' . $filename);
                    if (!file_exists($savePath)) {
                        if (!file_exists(public_path('images/products'))) {
                            mkdir(public_path('images/products'), 0777, true);
                        }
                        
                        $context = $this->createStreamContext(10);
                        try {
                            $imageContent = file_get_contents($fullUrl, false, $context);
                            if ($imageContent !== false) {
                                file_put_contents($savePath, $imageContent);
                                $image = 'images/products/' . $filename;
                                \Log::info("Successfully downloaded image: {$filename}");
                            } else {
                                \Log::warning("Failed to download image: {$fullUrl}");
                                $image = 'default-product.jpg';
                            }
                        } catch (\Exception $e) {
                            \Log::warning("Error downloading image {$fullUrl}: " . $e->getMessage());
                            $image = 'default-product.jpg';
                        }
                    } else {
                        $image = 'images/products/' . $filename;
                    }
                } else {
                    $image = 'default-product.jpg';
                }
                
                $urlProduct = $item->find('h3.s1-listProductTitle a', 0)?->href ?? '';
                
                // Clean the slug - extract only the path part
                $slug = $urlProduct;
                if (str_starts_with($slug, 'http')) {
                    $parsedUrl = parse_url($slug);
                    $slug = $parsedUrl['path'] ?? $slug;
                }
                
                $discount = '';
                $discountTag = $item->find('span.s1-discountBedge-value', 0);
                if ($discountTag) {
                    $discount = trim($discountTag->plaintext);
                }
                
                $indicators = [];
                $indicatorSpans = $item->find('p.indicators span.indicator');
                foreach ($indicatorSpans as $span) {
                    $indicators[] = trim($span->plaintext);
                }
                $indicatorsStr = implode(', ', $indicators);
                
                // Generate product code for level 4 - slug + category code
                $productCode = $this->generateProductCode($urlProduct) . '_' . $categoryCode;
                
                $products[] = [
                    'code' => $productCode,
                    'title' => $title,
                    'price' => $price_number,
                    'currency' => $price_currency,
                    'image' => $image,
                    'slug' => $slug,
                    'discount' => $discount,
                    'indicators' => $indicatorsStr,
                    'category_code' => $categoryCode
                ];
            }
            
            $allProducts = array_merge($allProducts, $products);
            
            // Check if there's a next page - simplified pagination detection
            $nextPageLink = $html->find('li.paginationItem a.buttonPaginationNext', 0);
            
            if ($nextPageLink) {
                \Log::info("Found pagination link: li.paginationItem a.buttonPaginationNext");
            } else {
                \Log::info("No pagination link found, stopping at page {$page}");
            }
            
            // Stop if no products found on current page OR no next page link
            if (empty($products)) {
                \Log::info("No products found on page {$page}, stopping pagination");
                break;
            }
            
            if (!$nextPageLink) {
                \Log::info("No valid next page link found, stopping at page {$page}");
                break;
            }
            
            $page++;
            
            // Build next page URL using the redirected URL as base
            $nextPageHref = $nextPageLink->href;
            \Log::info("Next page href: {$nextPageHref}");
            
            if ($redirectedUrl) {
                // Use the redirected URL as the base
                if (str_starts_with($nextPageHref, 'http')) {
                    // Full URL provided
                    $currentUrl = $nextPageHref;
                } else {
                    // Relative URL - combine with redirected URL
                    $parsedRedirected = parse_url($redirectedUrl);
                    $baseUrl = $parsedRedirected['scheme'] . '://' . $parsedRedirected['host'] . $parsedRedirected['path'];
                    
                    if (str_starts_with($nextPageHref, '?')) {
                        // Query parameter - append to base URL
                        $currentUrl = $baseUrl . $nextPageHref;
                    } else {
                        // Path - replace the path
                        $currentUrl = $baseUrl . '/' . ltrim($nextPageHref, '/');
                    }
                }
            } else {
                // Fallback to original logic
                $currentUrl = $currentUrl . $nextPageHref;
            }
            
            \Log::info("Next page URL: {$currentUrl}");
        }
        
        \Log::info("Total Level 4 products crawled across all pages: " . count($allProducts));
        return $allProducts;
    }
}


