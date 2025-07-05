# Hệ thống Failed Crawls

Hệ thống này giúp track và retry các bản ghi crawl bị lỗi để đảm bảo dữ liệu đầy đủ.

## Cấu trúc Database

### Bảng `failed_crawls`
- `id`: Primary key
- `type`: Loại crawl ('product', 'category_level2', 'category_level3', 'product_detail')
- `identifier`: Slug, code, hoặc URL của item bị lỗi
- `error`: Thông tin lỗi
- `attempts`: Số lần đã thử
- `last_attempt_at`: Thời gian thử cuối cùng
- `resolved_at`: Thời gian resolve (null = chưa resolve)
- `created_at`, `updated_at`: Timestamps

## Commands

### 1. Xem thống kê failed crawls
```bash
php artisan crawl:failed-stats
php artisan crawl:failed-stats --type=product_detail
```

### 2. Retry failed crawls
```bash
# Retry tất cả các loại
php artisan crawl:retry-failed

# Retry chỉ product details
php artisan crawl:retry-failed product_detail

# Retry chỉ level 2 categories
php artisan crawl:retry-failed category_level2

# Retry chỉ level 3 categories
php artisan crawl:retry-failed category_level3

# Giới hạn số lượng retry
php artisan crawl:retry-failed --limit=20
```

## Cách hoạt động

### 1. Tự động ghi lỗi
Khi một job crawl fail sau khi đã hết số lần retry (5 lần), hệ thống sẽ tự động:
- Ghi lại vào bảng `failed_crawls`
- Log thông tin lỗi
- Đánh dấu là chưa resolve

### 2. Retry thủ công
Sử dụng command `crawl:retry-failed` để:
- Lấy các bản ghi chưa resolve và có thể retry (< 5 attempts)
- Thực hiện crawl lại
- Mark as resolved nếu thành công
- Increment attempts nếu vẫn fail

### 3. Monitoring
Sử dụng command `crawl:failed-stats` để:
- Xem tổng số failed crawls
- Thống kê theo type
- Xem top errors
- Xem recent failures

## Workflow đề xuất

### Sau khi crawl xong:
1. Chạy `crawl:failed-stats` để xem có bao nhiêu lỗi
2. Chạy `crawl:retry-failed` để retry các lỗi
3. Chạy lại `crawl:failed-stats` để kiểm tra kết quả
4. Lặp lại cho đến khi không còn lỗi hoặc đã hết attempts

### Cron job (tùy chọn):
```bash
# Chạy retry tự động mỗi giờ
0 * * * * cd /path/to/project && php artisan crawl:retry-failed --limit=50
```

## Lưu ý

- Mỗi bản ghi chỉ được retry tối đa 5 lần
- Có delay 2-5 giây giữa các retry để tránh bị block
- Các lỗi tạm thời (timeout, connection) sẽ được retry
- Các lỗi vĩnh viễn sẽ không được retry
- Bảng `failed_crawls` khác với `failed_jobs` của Laravel 