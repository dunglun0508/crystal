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
        // Constructor để đảm bảo khởi tạo đúng cách
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
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'follow_location' => true,
                'max_redirects' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'timeout' => $timeout
            ]
        ]);
    }

    public function checkUrlAccessibility($url, $timeout = 15) {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'method' => 'HEAD',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'follow_location' => true,
                    'max_redirects' => 10
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'timeout' => $timeout,
                    'allow_self_signed' => true
                ]
            ]);
            
            $headers = @get_headers($url, 1, $context);
            
            if ($headers === false) {
                \Log::warning("checkUrlAccessibility: Failed to get headers for {$url}");
                return false;
            }
            
            $statusCode = $headers[0] ?? '';
            $isAccessible = strpos($statusCode, '200') !== false || strpos($statusCode, '301') !== false || strpos($statusCode, '302') !== false;
            
            if (!$isAccessible) {
                \Log::warning("checkUrlAccessibility: URL returned status {$statusCode} for {$url}");
            }
            
            return $isAccessible;
            
        } catch (\Exception $e) {
            \Log::warning("checkUrlAccessibility: Exception for {$url}: " . $e->getMessage());
            return false;
        }
    }

    private function downloadAndSaveCategoryImage($imageUrl, $categoryCode) {
        try {
            // Sửa URL ảnh nếu cần
            $imageUrl = $this->fixImageUrl($imageUrl);
            
            // Tạo thư mục nếu chưa tồn tại
            $uploadDir = public_path('images/categories');
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    \Log::error("Failed to create directory: {$uploadDir}");
                    return null;
                }
            }
            
            // Tạo tên file
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension) || strlen($extension) > 5) {
                $extension = 'jpg'; // Phần mở rộng mặc định
            }
            // Thay thế dấu / bằng dấu - để tránh tạo đường dẫn không hợp lệ
            $safeCategoryCode = str_replace('/', '-', $categoryCode);
            $filename = $safeCategoryCode . '.' . $extension;
            $filepath = rtrim($uploadDir, '/') . '/' . $filename;
            $relativePath = 'images/categories/' . $filename;
            


            // Nếu file đã tồn tại và dung lượng > 0 thì trả về luôn
            if (file_exists($filepath) && filesize($filepath) > 0) {
                return $relativePath;
            }
            
            // Tải ảnh với timeout nhỏ
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'timeout' => 10
                ]
            ]);
            $imageContent = @file_get_contents($imageUrl, false, $context);
            
            if ($imageContent !== false && strlen($imageContent) > 100) { // 100 bytes là ảnh hợp lệ tối thiểu
                if (file_put_contents($filepath, $imageContent)) {
                    \Log::info("Category image downloaded: {$imageUrl} -> {$filepath}");
                    return $relativePath;
                } else {
                    \Log::error("Failed to save category image: {$filepath}");
                }
            } else {
                \Log::error("Failed to download or empty category image: {$imageUrl}");
            }
        } catch (\Exception $e) {
            \Log::error("Error downloading category image {$imageUrl}: " . $e->getMessage());
        }
        return null;
    }

    public function categoriesCrawler(Request $request){
        \Log::info("=== CATEGORIES CRAWL START ===");
        
        try {
            include_once 'simple_html_dom.php';
            $url = 'https://www.artcrystal.eu/';
            $html = file_get_html($url);
            if (!$html) {
                \Log::error("CategoriesCrawler: Failed to load URL");
                return response()->json(['error' => 'Failed to load URL']);
            }
        
        $categories = [];
        $id = 1;
        $crawledCount = 0;
        $levelCounts = [1 => 0, 2 => 0, 3 => 0];
        
        // Tìm các mục menu chính
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
                        'parent_code' => null,
                        'level' => 1,
                        'code' => $categoryCode,
                        'image' => null
                    ];
                    $crawledCount++;
                    $levelCounts[1]++;
                    
                    // Đệ quy parse submenu cấp dưới
                    $submenu = $item->find('ul.s1-submenu-items.level-2 li.s1-submenu-item.level-2');
                    if (!empty($submenu)) {
                        $this->parseMenuRecursive($submenu, $href, $id, $categoryCode, 2, $categories, $crawledCount, $levelCounts);
                    }
                }
            }
        }
        
        \Log::info("Total categories collected: " . count($categories));
        
        // Thao tác với database
        $insertCount = 0;
        $updateCount = 0;
        $deleteCount = 0;
        
        // Lấy số lượng category hiện có trước khi thao tác
        $existingCount = Category::count();
        
        foreach ($categories as $cat) {
            $existingCategory = Category::where('slug', $cat['slug'])->first();
            
            if ($existingCategory) {
                $existingCategory->update([
                    'title' => $cat['title'] ?? '',
                    'parent_code' => $cat['parent_code'] ?? null,
                    'level' => $cat['level'] ?? 1,
                    'code' => $cat['code'] ?? '',
                    'image' => $cat['image'] ?? $existingCategory->image ?? null
                ]);
                $updateCount++;
            } else {
                Category::create([
                    'title' => $cat['title'] ?? '',
                    'slug' => $cat['slug'] ?? '',
                    'parent_code' => $cat['parent_code'] ?? null,
                    'level' => $cat['level'] ?? 1,
                    'code' => $cat['code'] ?? '',
                    'image' => $cat['image'] ?? null
                ]);
                $insertCount++;
            }
        }
        
        // Lấy số lượng category sau khi thao tác
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
        } catch (\Exception $e) {
            \Log::error("CategoriesCrawler: Exception occurred: " . $e->getMessage());
            \Log::error("CategoriesCrawler: Stack trace: " . $e->getTraceAsString());
            return response()->json(['error' => 'Exception occurred: ' . $e->getMessage()]);
        }
    }

    private function parseMenuRecursive($elements, $url, &$id, $parentCode, $level, &$categories, &$crawledCount, &$levelCounts) {
        foreach ($elements as $item) {
            $link = $item->find('a.s1-submenu-link', 0);
            if ($link) {
                $title = trim($link->plaintext);
                $href = $link->href;
                
                if ($title && $href && !str_contains($href, 'javascript:') && !str_contains($href, '#')) {
                    $categoryCode = $href . '_' . $level;
                    
                    // Lấy ảnh cho các category level 2
                    $image = null;
                    if ($level == 2) {
                        $imageElement = $item->find('a.s1-submenu-image img.s1-submenu-img', 0);
                        if ($imageElement) {
                            $imageUrl = $imageElement->getAttribute('data-src') ?: $imageElement->getAttribute('src');
                            if ($imageUrl) {
                                $image = $this->downloadAndSaveCategoryImage($imageUrl, $categoryCode);
                            }
                        }
                    }
                    
                    $categories[] = [
                        'id' => $id++,
                        'title' => $title ?? '',
                        'slug' => $href ?? '',
                        'parent_code' => $parentCode ?? null,
                        'level' => $level ?? 1,
                        'code' => $categoryCode ?? '',
                        'image' => $image ?? null
                    ];
                    $crawledCount++;
                    $levelCounts[$level]++;
                    
                    // Đệ quy parse submenu cấp dưới
                    $submenu = $item->find('ul.s1-submenu-items li.s1-submenu-item.level-3');
                    if (!empty($submenu)) {
                        $this->parseMenuRecursive($submenu, $href, $id, $categoryCode, $level + 1, $categories, $crawledCount, $levelCounts);
                    }
                }
            }
        }
    }

    public function productsCrawler(Request $request){
        \Log::info("=== LEVEL 3 PRODUCTS CRAWL START ===");
        $this->crawlProducts();
        \Log::info("=== LEVEL 3 PRODUCTS CRAWL COMPLETE ===");
        return response()->json(['success' => true, 'message' => 'Products crawling completed']);
    }

    public function crawlLevel2Products(Request $request){
        \Log::info("=== LEVEL 2 PRODUCTS CRAWL START ===");
        $this->crawlLevel2ProductsInternal();
        \Log::info("=== LEVEL 2 PRODUCTS CRAWL COMPLETE ===");
        return response()->json(['success' => true, 'message' => 'Level 2 products crawling completed']);
    }

    public function dailyCrawler(Request $request) {
        // Crawl categories trước
        $this->categoriesCrawler($request);
        
        // Sau đó crawl products
        $this->crawlProducts();
        
        // Sau đó crawl level 2 products
        $this->crawlLevel2ProductsInternal();
        
        // Cuối cùng crawl product details
        $this->crawlProductDetails();
        
        return response()->json(['success' => true, 'message' => 'Daily crawling completed']);
    }

    private function crawlProducts() {
        $categories = Category::where('level', 3)->get();
        $totalCategories = $categories->count();
        
        \Log::info("Starting crawlProducts for {$totalCategories} level 3 categories");
        
        foreach ($categories as $index => $cat) {
            
            $baseUrl = 'https://www.artcrystal.eu';
            $products = $this->crawlProductsByCategorySlug($baseUrl . $cat->slug, $cat->code);
            
            if (!empty($products)) {
                foreach ($products as $productData) {
                    $productData['price'] = $productData['price'] ?: null;
                    $productData['image'] = $productData['image'] ?? '';
                    $existing = Product::where('code', $productData['code'])->first();
                    if ($existing) {
                        $updateData = $productData;
                        unset($updateData['slug']);
                        $existing->update($updateData);
                    } else {
                        Product::create($productData);
                    }
                }
            }
        }
    }

    // Crawl products theo category slug
    public function crawlProductsByCategorySlug($url, $categoryCode) {
        if (!function_exists('file_get_html')) {
            include_once app_path('Http/Controllers/simple_html_dom.php');
        }
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'follow_location' => true,
                'max_redirects' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'timeout' => 30,
                'allow_self_signed' => true
            ]
        ]);
        
        $allProducts = [];
        $page = 1;
        $maxPages = 20; // Giới hạn hợp lý cho hầu hết categories
        $currentUrl = $url;
        $redirectedUrl = null; // Lưu URL thực tế sau redirect
        
        while ($page <= $maxPages) {
            
            $html = file_get_html($currentUrl, false, $context);
            if (!$html) {
                \Log::warning("crawlProductsByCategorySlug: Failed to load URL: {$currentUrl}");
                throw new \Exception("Failed to load URL: {$currentUrl}");
            }
            
            // Trang đầu tiên: lấy actual URL sau redirect
            if ($page === 1) {
                // Dùng function mới để lấy final URL sau redirect
                $finalUrl = $this->getFinalRedirectUrl($currentUrl);
                if ($finalUrl && $finalUrl !== $currentUrl) {
                    $redirectedUrl = $finalUrl;
                    \Log::info("Detected redirected URL: {$redirectedUrl}");
                } else {
                    // Fallback: dùng original URL nếu không redirect
                    $redirectedUrl = $currentUrl;
                    \Log::info("No redirect detected, using original URL: {$redirectedUrl}");
                }
            }
            
            $products = [];
            
            // Kiểm tra danh sách sản phẩm (ul.productListFGrid li.s1-gridItem.s1-itemBuyable) - Sản phẩm level 3
            $productItems = $html->find('ul.productListFGrid li.s1-gridItem.s1-itemBuyable');
            
            if (empty($productItems)) {
                break;
            }
            
            \Log::info("Found " . count($productItems) . " products on page {$page}");
            
            // Xử lý sản phẩm level 3 (chỉ có 1 case duy nhất)
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
                    $image = $this->downloadAndSaveProductImage($imageUrl, $this->generateProductCode($url));
                }
                
                $urlProduct = $item->find('h3.s1-listProductTitle a', 0)?->href ?? '';
                
                // Clean slug: chỉ lấy phần path
                $slug = $urlProduct;
                if (str_starts_with($slug, 'http')) {
                    $parsedUrl = parse_url($slug);
                    $slug = $parsedUrl['path'] ?? $slug;
                }
                
                // Normalize slug để loại bỏ prefix ngôn ngữ
                $slug = $this->normalizeProductSlug($slug);
                
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
                $indicatorsStr = json_encode($indicators);
                
                // Tạo mã sản phẩm cho level 3 - slug + mã category
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
            
            // Kiểm tra next page (pagination detection đơn giản)
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
                // Full URL provided
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
                // Fallback: dùng logic cũ
                $currentUrl = $currentUrl . $nextPageHref;
            }
            
            \Log::info("Next page URL: {$currentUrl}");
        }
        
        \Log::info("Total products crawled across all pages: " . count($allProducts));
        return $allProducts;
    }

    private function normalizeProductSlug($url) {
        // Nếu là URL đầy đủ, chỉ lấy phần path
        $parsed = parse_url($url);
        if (isset($parsed['path'])) {
            $url = $parsed['path'];
        }
        // Loại bỏ prefix ngôn ngữ (/en/, /cs/, /de/, etc.) - hỗ trợ 2-3 ký tự
        $url = preg_replace('/^\/[a-z]{2,3}\//', '/', $url);
        // Đảm bảo bắt đầu bằng /
        if ($url && $url[0] !== '/') {
            $url = '/' . $url;
        }
        return $url;
    }
    
    private function generateProductCode($url) {
        // Lấy mã định danh sản phẩm từ URL
        $urlParts = explode('/', trim($url, '/'));
        // Tìm phần có chứa cả chữ và số (slug sản phẩm)
        $productSlug = '';
        foreach ($urlParts as $part) {
            if (preg_match('/[a-zA-Z]/', $part) && preg_match('/[0-9]/', $part)) {
                $productSlug = $part;
                break;
            }
        }
        // Nếu không tìm được slug phù hợp, dùng phần cuối cùng
        if (empty($productSlug)) {
            $productSlug = end($urlParts);
        }
        // Làm sạch slug sản phẩm - chỉ giữ lại ký tự chữ, số, gạch ngang, gạch dưới
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $productSlug);
    }

    public function crawlProductDetail($url, $slug = null) {
        if (!function_exists('file_get_html')) {
            include_once app_path('Http/Controllers/simple_html_dom.php');
        }
        $baseUrl = 'https://www.artcrystal.eu';
        $fullUrl = str_starts_with($url, 'http') ? $url : $baseUrl . $url;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'follow_location' => true,
                'max_redirects' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'timeout' => 15,
                'allow_self_signed' => true
            ]
        ]);
        
        $checkContext = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'HEAD',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'follow_location' => true,
                'max_redirects' => 3
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'timeout' => 10,
                'allow_self_signed' => true
            ]
        ]);
        
        $headers = @get_headers($fullUrl, 1, $checkContext);
        if ($headers === false) {
            \Log::warning("crawlProductDetail: Failed to get headers for {$fullUrl}");
            throw new \Exception("Failed to get headers for {$fullUrl}");
        }
        
        $statusCode = $headers[0] ?? '';
        if (strpos($statusCode, '200') === false && strpos($statusCode, '301') === false && strpos($statusCode, '302') === false) {
            \Log::warning("crawlProductDetail: HTTP Error - {$fullUrl} - Status: {$statusCode}");
            throw new \Exception("HTTP Error - {$fullUrl} - Status: {$statusCode}");
        }
        
        $html = file_get_html($fullUrl, false, $context);
        if (!$html) {
            \Log::warning("crawlProductDetail: Failed to load URL: {$fullUrl}");
            throw new \Exception("Failed to load URL: {$fullUrl}");
        }
        
        // Lấy gallery ảnh chi tiết sản phẩm
        $gallery = [];
        $galleryLocal = [];
        foreach ($html->find('div.s1-detailGallery figure.galleryItem') as $fig) {
            $img = $fig->getAttribute('data-full') ?? '';
            if ($img) {
                $fullImageUrl = $this->fixImageUrl($img);
                
                if (filter_var($fullImageUrl, FILTER_VALIDATE_URL)) {
                    $filename = basename(parse_url($img, PHP_URL_PATH));
                    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
                    
                    $imagePath = $this->downloadAndSaveProductImage($fullImageUrl, $filenameWithoutExt);
                    if ($imagePath) {
                        $gallery[] = $fullImageUrl;
                        $galleryLocal[] = $imagePath;
                    }
                } else {
                    \Log::warning("Invalid image URL: {$img}");
                }
            }
        }
        
        // Lấy detail indicators
        $indicators = [];
        foreach ($html->find('div.s1-detailImgOuter p.indicators span.indicator') as $span) {
            $indicators[] = trim($span->plaintext);
        }
        $indicatorsStr = json_encode($indicators);
        
        // Lấy meta description
        $metaDescription = $html->find('meta[itemprop=description]', 0)?->content ?? '';
        
        // Lấy long description từ div.ac-product__long-cont
        $longDesc = '';
        $longDescDiv = $html->find('div.ac-product__long-cont div.userHTMLContent', 0);
        if ($longDescDiv) {
            $longDesc = trim($longDescDiv->innertext());
        }
        
        // Lấy specs từ table
        $specs = [];
        foreach ($html->find('table.tabAdditionalInfo tr') as $tr) {
            $key = trim($tr->find('td.tabAdditionalInfoTitle', 0)?->plaintext ?? '');
            $val = trim($tr->find('td', 1)?->plaintext ?? '');
            if ($key && $val) {
                $specs[$key] = $val;
            }
        }
        
        // Lấy key features (tìm đúng cấu trúc)
        $features = [];
        // Tìm các list item trong key features
        $featuresDiv = $html->find('div.ac-product__key-features', 0);
        if ($featuresDiv) {
            foreach ($html->find('div.ac-product__key-features ul li') as $li) {
                $features[] = trim($li->plaintext);
            }
        }
        
        // Nếu không có feature ở key features thì thử chỗ khác
        if (empty($features)) {
            // Tìm feature ở highlights section
            foreach ($html->find('ul.highlightsList li.highlightsItem strong.highlightsFigTitle') as $title) {
                $features[] = trim($title->plaintext);
            }
        }
        
        // Lấy variant info từ s1-buttonRows-cont
        $variants = [];
        foreach ($html->find('div.s1-buttonRows-cont div.s1-buttonRow') as $row) {
            $variant = [];
            
            // Lấy tên variant
            $variantName = $row->find('span.s1-buttonRow-txt', 0);
            if ($variantName) {
                $variant['name'] = trim($variantName->plaintext);
            }
            
            // Lấy Art.No.
            $artNo = $row->find('p.s1-buttonRow-line span.s1-buttonRow-txt', 0);
            if ($artNo) {
                $variant['art_no'] = trim($artNo->plaintext);
            }
            
            // Lấy price
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

    // Public wrapper cho Job gọi
    public function crawlProductDetailForJob($url, $slug = null) {
        return $this->crawlProductDetail($url, $slug);
    }

    public function crawlLevel2ProductsByCategorySlug($url, $categoryCode) {
        if (!function_exists('file_get_html')) {
            include_once app_path('Http/Controllers/simple_html_dom.php');
        }
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'follow_location' => true,
                'max_redirects' => 10
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'timeout' => 30,
                'allow_self_signed' => true
            ]
        ]);
        
        $allProducts = [];
        $page = 1;
        $maxPages = 20; // Giới hạn hợp lý cho hầu hết categories
        $currentUrl = $url;
        $redirectedUrl = null; // Lưu URL thực tế sau redirect
        
        while ($page <= $maxPages) {
            \Log::info("Crawling Level 2 page {$page}: {$currentUrl}");
            
            $html = file_get_html($currentUrl, false, $context);
            if (!$html) {
                \Log::warning("crawlLevel2ProductsByCategorySlug: Failed to load URL: {$currentUrl}");
                throw new \Exception("Failed to load URL: {$currentUrl}");
            }
            
            // Trang đầu tiên: lấy actual URL sau redirect
            if ($page === 1) {
                // Dùng function mới để lấy final URL sau redirect
                $finalUrl = $this->getFinalRedirectUrl($currentUrl);
                if ($finalUrl && $finalUrl !== $currentUrl) {
                    $redirectedUrl = $finalUrl;
                    \Log::info("Detected redirected URL: {$redirectedUrl}");
                } else {
                    // Fallback: dùng original URL nếu không redirect
                    $redirectedUrl = $currentUrl;
                    \Log::info("No redirect detected, using original URL: {$redirectedUrl}");
                }
            }
            
            $products = [];
            
            // Chỉ có 2 trường hợp cho level 2 products
            $productItems = [];
            
            // Trường hợp 1: Grid products với price, indicators (ul.productListFGrid li.s1-gridItem.s1-itemBuyable)
            $productItems = $html->find('ul.productListFGrid li.s1-gridItem.s1-itemBuyable');
            if (!empty($productItems)) {
                \Log::info("Found grid products using selector: ul.productListFGrid li.s1-gridItem.s1-itemBuyable");
            }
            
            // Trường hợp 2: Directory products chỉ có title và image (div.directoryList a.directoryListItem)
            if (empty($productItems)) {
                $productItems = $html->find('div.directoryList a.directoryListItem');
                if (!empty($productItems)) {
                    \Log::info("Found directory products using selector: div.directoryList a.directoryListItem");
                }
            }
            
            if (empty($productItems)) {
                \Log::info("No more Level 2 products found on page {$page}, stopping pagination");
                break;
            }
            
            \Log::info("Found " . count($productItems) . " products on page {$page}");
            
            // Xử lý sản phẩm dựa trên loại selector được tìm thấy
            $isDirectoryProducts = false;
            foreach ($productItems as $item) {
                // Kiểm tra xem có phải directory products không
                if ($item->tag === 'a' && strpos($item->class, 'directoryListItem') !== false) {
                    $isDirectoryProducts = true;
                    break;
                }
            }
            
            if ($isDirectoryProducts) {
                // Xử lý directory products (chỉ có title và image)
                foreach ($productItems as $item) {
                    $title = trim($item->find('span.directoryListLink', 0)?->plaintext ?? '');
                    
                    $imgTag = $item->find('img', 0);
                    $imageUrl = $imgTag?->getAttribute('data-src') ?? $imgTag?->src ?? '';
                    
                    $image = '';
                    if ($imageUrl) {
                        $image = $this->downloadAndSaveProductImage($imageUrl, $this->generateProductCode($item->href));
                    }
                    
                    $urlProduct = $item->href ?? '';
                    
                    // Clean slug: chỉ lấy phần path
                    $slug = $urlProduct;
                    if (str_starts_with($slug, 'http')) {
                        $parsedUrl = parse_url($slug);
                        $slug = $parsedUrl['path'] ?? $slug;
                    }
                    
                    // Normalize slug để loại bỏ prefix ngôn ngữ
                    $slug = $this->normalizeProductSlug($slug);
                    
                    // Tạo mã sản phẩm cho level 2 - slug + mã category
                    $productCode = $this->generateProductCode($urlProduct) . '_' . $categoryCode;
                    
                    $products[] = [
                        'code' => $productCode,
                        'title' => $title,
                        'price' => '', // Directory products không có price
                        'currency' => '', // Directory products không có currency
                        'image' => $image,
                        'slug' => $slug,
                        'discount' => '', // Directory products không có discount
                        'indicators' => '[]', // Directory products không có indicators
                        'category_code' => $categoryCode
                    ];
                }
            } else {
                // Xử lý grid products (có đầy đủ thông tin)
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
                        $image = $this->downloadAndSaveProductImage($imageUrl, $this->generateProductCode($url));
                    }
                    
                    $urlProduct = $item->find('h3.s1-listProductTitle a', 0)?->href ?? '';
                    
                    // Clean slug: chỉ lấy phần path
                    $slug = $urlProduct;
                    if (str_starts_with($slug, 'http')) {
                        $parsedUrl = parse_url($slug);
                        $slug = $parsedUrl['path'] ?? $slug;
                    }
                    
                    // Normalize slug để loại bỏ prefix ngôn ngữ
                    $slug = $this->normalizeProductSlug($slug);
                    
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
                    $indicatorsStr = json_encode($indicators);
                    
                    // Tạo mã sản phẩm cho level 2 - slug + mã category
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
            }
            
            $allProducts = array_merge($allProducts, $products);
            
            // Kiểm tra next page (pagination detection đơn giản)
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
                // Full URL provided
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
                // Fallback: dùng logic cũ
                $currentUrl = $currentUrl . $nextPageHref;
            }
            
            \Log::info("Next page URL: {$currentUrl}");
        }
        
        \Log::info("Total Level 2 products crawled across all pages: " . count($allProducts));
        return $allProducts;
    }

    private function crawlLevel2ProductsInternal() {
        // Chỉ lấy các category level 2 mà không có category con level 3
        $categories = Category::where('level', 2)
            ->whereNotIn('code', function($query) {
                $query->select('parent_code')
                      ->from('categories')
                      ->where('level', 3)
                      ->whereNotNull('parent_code');
            })
            ->get();
        $totalCategories = $categories->count();
        
        \Log::info("Starting crawlLevel2ProductsInternal for {$totalCategories} level 2 categories (without level 3 children)");
        
        foreach ($categories as $index => $cat) {
            $progress = round(($index + 1) / $totalCategories * 100, 1);
            \Log::info("crawlLevel2ProductsInternal: Processing category " . ($index + 1) . "/{$totalCategories}: {$cat->slug} (level {$cat->level}) ({$progress}%)");
            
            $baseUrl = 'https://www.artcrystal.eu';
            $products = $this->crawlLevel2ProductsByCategorySlug($baseUrl . $cat->slug, $cat->code);
            
            if (!empty($products)) {
                foreach ($products as $productData) {
                    $productData['price'] = $productData['price'] ?: null;
                    $productData['image'] = $productData['image'] ?? '';
                    $existing = Product::where('code', $productData['code'])->first();
                    if ($existing) {
                        $updateData = $productData;
                        unset($updateData['slug']);
                        $existing->update($updateData);
                    } else {
                        Product::create($productData);
                    }
                }
                
                \Log::info("crawlLevel2ProductsInternal: Category {$cat->slug} - " . count($products) . " products processed");
            } else {
                \Log::warning("crawlLevel2ProductsInternal: No products found for category {$cat->slug}");
            }
        }
        
        \Log::info("crawlLevel2ProductsInternal: Completed for all level 2 categories");
    }

    public function crawlProductDetails() {
        \Log::info("=== PRODUCT DETAILS CRAWL START ===");
        
        // Query các slug distinct từ bảng products
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
            
            // Lấy product code từ slug
            $product = Product::where('slug', $slug)->first();
            if (!$product) {
                \Log::warning("crawlProductDetails: No product found for slug {$slug}");
                continue;
            }
            
            // Lưu product detail
            $existingDetail = ProductDetail::where('code', $product->code)->first();
            
            if ($existingDetail) {
                // Kiểm tra data thay đổi trước khi update
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
                
                // So sánh từng field
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
        \Log::info("=== PRODUCT DETAILS CRAWL COMPLETE ===");
    }

    private function downloadAndSaveProductImage($imageUrl, $filenameWithoutExt) {
        $maxRetries = 3;
        $retryCount = 0;
        
        // Thay thế dấu / bằng dấu - để tránh tạo đường dẫn không hợp lệ
        $safeFilename = str_replace('/', '-', $filenameWithoutExt);
        
        while ($retryCount < $maxRetries) {
            try {
                $imageUrl = $this->fixImageUrl($imageUrl);
                $uploadDir = public_path('images/products');
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true)) {
                        \Log::error("Failed to create directory: {$uploadDir}");
                        return $this->createPlaceholderImage($safeFilename);
                    }
                }
                
                $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                if (empty($extension) || strlen($extension) > 5) {
                    $extension = 'jpg';
                }
                $filename = $safeFilename . '.' . $extension;
                $filepath = rtrim($uploadDir, '/') . '/' . $filename;
                $relativePath = 'images/products/' . $filename;
                
                // Nếu file đã tồn tại và dung lượng > 0 thì trả về luôn
                if (file_exists($filepath) && filesize($filepath) > 0) {
                    return $relativePath;
                }
                
                // Tải ảnh với timeout nhỏ
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 8,
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'timeout' => 8
                    ]
                ]);
                
                $imageContent = @file_get_contents($imageUrl, false, $context);
                
                if ($imageContent !== false && strlen($imageContent) > 100) {
                    if (file_put_contents($filepath, $imageContent)) {
                        \Log::info("Product image downloaded successfully: {$imageUrl} -> {$filepath}");
                        return $relativePath;
                    } else {
                        \Log::error("Failed to save product image: {$filepath}");
                    }
                } else {
                    \Log::warning("Failed to download or empty product image (attempt " . ($retryCount + 1) . "): {$imageUrl}");
                }
                
            } catch (\Exception $e) {
                \Log::error("Error downloading product image (attempt " . ($retryCount + 1) . ") {$imageUrl}: " . $e->getMessage());
            }
            
            $retryCount++;
            
            // Nếu còn retry, đợi 1 giây rồi thử lại
            if ($retryCount < $maxRetries) {
                sleep(1);
            }
        }
        
        // Nếu tất cả retry đều thất bại, tạo ảnh placeholder
        $placeholderPath = $this->createPlaceholderImage($safeFilename);
        \Log::warning("All retries failed for image: {$imageUrl}, using placeholder: {$placeholderPath}");
        return $placeholderPath;
    }
    
    private function createPlaceholderImage($safeFilename) {
        try {
            $uploadDir = public_path('images/products');
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    \Log::error("Failed to create directory: {$uploadDir}");
                    return 'images/products/placeholder.jpg';
                }
            }
            
            $filename = $safeFilename . '_placeholder.jpg';
            $filepath = rtrim($uploadDir, '/') . '/' . $filename;
            $relativePath = 'images/products/' . $filename;
            
            // Tạo ảnh placeholder đơn giản (1x1 pixel màu xám)
            $image = imagecreate(1, 1);
            $gray = imagecolorallocate($image, 128, 128, 128);
            imagefill($image, 0, 0, $gray);
            imagejpeg($image, $filepath, 80);
            imagedestroy($image);
            
            \Log::info("Created placeholder image: {$filepath}");
            return $relativePath;
            
        } catch (\Exception $e) {
            \Log::error("Failed to create placeholder image: " . $e->getMessage());
            // Fallback: trả về đường dẫn ảnh placeholder có sẵn
            return 'images/products/placeholder.jpg';
        }
    }
}


