# Hệ Thống Crawl - Crystal Project

## Tổng Quan

Hệ thống crawl được thiết kế để thu thập dữ liệu từ website crystal với cấu trúc phân cấp:
- **Level 1**: Categories chính
- **Level 2**: Sub-categories (có thể có products trực tiếp hoặc sub-categories)
- **Level 3**: Sub-categories cuối cùng (chỉ có products)

## Cấu Trúc Database

### Bảng Chính
- `categories`: Lưu thông tin categories
- `products`: Lưu thông tin sản phẩm cơ bản
- `product_details`: Chi tiết sản phẩm (sử dụng slug làm code)
- `product_variants`: Các biến thể sản phẩm (sử dụng slug làm code)
- `failed_crawls`: Lưu các lỗi crawl để retry sau

### Quan Hệ
- Category có thể có nhiều sub-categories (parent_id)
- Category có thể có nhiều products
- Product có 1 ProductDetail và nhiều ProductVariants

## Các Lệnh Crawl

### 1. Crawl Toàn Bộ
```bash
# Crawl tất cả: categories, products, details
php artisan crawl:run

# Chỉ crawl categories
php artisan crawl:run categories

# Chỉ crawl products (level 2 + level 3)
php artisan crawl:run products

# Chỉ crawl product details
php artisan crawl:run details
```

### 2. Crawl Theo Loại
```bash
# Crawl categories level 1
php artisan crawl:categories

# Crawl products level 2
php artisan crawl:level2-products

# Crawl products level 3
php artisan crawl:level3-products

# Crawl product details
php artisan crawl:product-details
```

### 3. Kiểm Tra Trạng Thái
```bash
# Xem thống kê failed crawls
php artisan crawl:failed-stats

# Xem log categories
php artisan crawl:check-categories-log

# Xem queue status
php artisan crawl:check-queue
```

## Hệ Thống Retry

### Retry Failed Crawls
```bash
# Retry tất cả failed crawls
php artisan crawl:retry-failed

# Retry theo loại
php artisan crawl:retry-failed category_level2
php artisan crawl:retry-failed category_level3
php artisan crawl:retry-failed product_detail

# Giới hạn số lượng retry
php artisan crawl:retry-failed --limit=10
```

### Retry Failed Jobs
```bash
# Retry tất cả failed queue jobs
php artisan queue:retry-failed

# Retry job cụ thể
php artisan queue:retry {job_id}
```

## Cấu Trúc Jobs

### 1. CrawlCategoriesJob
- **Mục đích**: Crawl categories level 1
- **Input**: Không cần
- **Output**: Tạo categories trong database
- **Log**: `CRAWLING CATEGORIES STARTED` / `CRAWLING CATEGORIES COMPLETED`

### 2. CrawlLevel2ProductsJob
- **Mục đích**: Crawl products từ categories level 2
- **Input**: categoryCode, categorySlug
- **Xử lý**: 2 trường hợp
  - **Case 1**: Grid products (`.product-grid`)
  - **Case 2**: Directory products (`.product-directory`)
- **Log**: `CRAWLING LEVEL 2 PRODUCTS STARTED` / `CRAWLING LEVEL 2 PRODUCTS COMPLETED`

### 3. CrawlLevel3ProductsJob
- **Mục đích**: Crawl products từ categories level 3
- **Input**: categoryCode, categorySlug
- **Xử lý**: Chỉ grid products (`.product-grid`)
- **Log**: `CRAWLING LEVEL 3 PRODUCTS STARTED` / `CRAWLING LEVEL 3 PRODUCTS COMPLETED`

### 4. CrawlProductDetailsAndVariantsJob
- **Mục đích**: Crawl chi tiết và variants của sản phẩm
- **Input**: productSlug
- **Output**: ProductDetail và ProductVariants
- **Log**: `CRAWLING PRODUCT DETAILS STARTED` / `CRAWLING PRODUCT DETAILS COMPLETED`

### 5. DispatchCrawlJobs
- **Mục đích**: Dispatch các jobs crawl theo thứ tự
- **Thứ tự**: Categories → Level 2 Products → Level 3 Products → Product Details

## Cấu Hình

### Timeout Settings
```php
// Trong các Job classes
$timeout = 30; // seconds
$retryDelay = rand(2, 5); // seconds
```

### User Agent Rotation
```php
$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    // ...
];
```

### Rate Limiting
- Delay giữa các requests: 2-5 giây
- Random delay để tránh detection
- Respect robots.txt

## Monitoring & Logging

### Log Files
- `storage/logs/laravel.log`: Log chính
- `storage/logs/crawl.log`: Log crawl riêng (nếu có)

### Log Levels
- **INFO**: Bắt đầu/hoàn thành crawl
- **WARNING**: Lỗi nhẹ, có thể retry
- **ERROR**: Lỗi nghiêm trọng, cần can thiệp

### Metrics
- Số lượng items crawled
- Số lượng items thêm/cập nhật/xóa
- Thời gian crawl
- Tỷ lệ thành công/thất bại

## Troubleshooting

### Lỗi Thường Gặp

#### 1. Queue Jobs Không Chạy
```bash
# Kiểm tra queue worker
php artisan queue:work

# Xem failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry-failed
```

#### 2. Network Timeout
```bash
# Kiểm tra failed crawls
php artisan crawl:failed-stats

# Retry failed crawls
php artisan crawl:retry-failed
```

#### 3. Memory Issues
```bash
# Tăng memory limit trong php.ini
memory_limit = 512M

# Hoặc chạy với memory limit
php -d memory_limit=512M artisan crawl:run
```

#### 4. Database Lock
```bash
# Kiểm tra database connections
php artisan tinker
DB::connection()->getPdo();

# Restart queue workers
php artisan queue:restart
```

### Debug Commands
```bash
# Xem log real-time
tail -f storage/logs/laravel.log

# Kiểm tra database
php artisan tinker
App\Models\Category::count();
App\Models\Product::count();

# Test crawl riêng lẻ
php artisan crawl:categories
```

## Best Practices

### 1. Ethical Crawling
- Respect robots.txt
- Sử dụng delays hợp lý
- Rotate User-Agents
- Crawl trong giờ thấp điểm

### 2. Performance
- Sử dụng queue để xử lý async
- Batch processing cho large datasets
- Index database columns quan trọng
- Monitor memory usage

### 3. Reliability
- Implement retry logic
- Track failed items
- Backup data trước khi crawl
- Monitor system resources

### 4. Maintenance
- Clean up old failed crawls
- Archive old logs
- Monitor disk space
- Regular database optimization

## Development

### Thêm Job Mới
1. Tạo Job class trong `app/Jobs/`
2. Implement `handle()` method
3. Thêm vào `DispatchCrawlJobs` nếu cần
4. Tạo command nếu cần test riêng

### Thêm Error Handling
1. Wrap code trong try-catch
2. Log error với context
3. Thêm vào `FailedCrawl` nếu cần retry
4. Update retry logic

### Testing
```bash
# Test crawl riêng lẻ
php artisan crawl:categories

# Test với limit
php artisan crawl:run --limit=10

# Test retry
php artisan crawl:retry-failed --limit=5
```

## Support

Nếu gặp vấn đề:
1. Kiểm tra logs trước
2. Chạy failed stats
3. Thử retry failed items
4. Kiểm tra system resources
5. Liên hệ team nếu cần

---

**Lưu ý**: Hệ thống này được thiết kế để crawl dữ liệu một cách có trách nhiệm và hiệu quả. Hãy đảm bảo tuân thủ các quy định và best practices khi sử dụng. 