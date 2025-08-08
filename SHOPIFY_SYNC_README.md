# Shopify Sync System với Background Jobs

Hệ thống đồng bộ dữ liệu lên Shopify sử dụng background jobs để tránh timeout và xử lý hiệu quả.

## 🚀 **Cách sử dụng**

### **1. Chạy Queue Worker**

Trước tiên, bạn cần chạy queue worker để xử lý các jobs:

```bash
# Chạy queue worker cho queue shopify-sync
php artisan queue:work --queue=shopify-sync

# Hoặc chạy tất cả queues
php artisan queue:work
```

### **2. Sử dụng Console Commands**

#### **Đồng bộ tất cả Categories:**
```bash
php artisan shopify:sync categories
```

#### **Đồng bộ tất cả Products:**
```bash
php artisan shopify:sync products
```

#### **Đồng bộ Products theo Category:**
```bash
php artisan shopify:sync category-products --category=CATEGORY_CODE
```

### **3. Sử dụng Web Routes**

#### **Kiểm tra kết nối:**
```
GET /shopify/check-connection
```

#### **Đồng bộ 1 Category:**
```
POST /shopify/sync-category/{category}
```

#### **Đồng bộ 1 Product:**
```
POST /shopify/sync-product/{product}
```

#### **Đồng bộ tất cả Categories:**
```
GET /shopify/sync-all-categories
```

#### **Đồng bộ tất cả Products:**
```
GET /shopify/sync-all-products
```

#### **Đồng bộ Products theo Category:**
```
GET /shopify/sync-products-by-category/{category}
```

## 📋 **Các Jobs đã tạo**

### **1. SyncCategoryToShopifyJob**
- Đồng bộ 1 category lên Shopify
- Timeout: 5 phút
- Retry: 3 lần

### **2. SyncProductToShopifyJob**
- Đồng bộ 1 product lên Shopify
- Timeout: 5 phút
- Retry: 3 lần

### **3. SyncAllCategoriesToShopifyJob**
- Dispatch jobs cho tất cả categories
- Timeout: 10 phút
- Retry: 1 lần

### **4. SyncAllProductsToShopifyJob**
- Dispatch jobs cho tất cả products
- Timeout: 10 phút
- Retry: 1 lần

### **5. SyncProductsByCategoryJob**
- Dispatch jobs cho products theo category
- Timeout: 10 phút
- Retry: 1 lần

## 🔧 **Cấu hình Queue**

### **1. Cấu hình Database Queue**

Trong file `.env`:
```env
QUEUE_CONNECTION=database
```

### **2. Tạo bảng jobs (nếu chưa có):**
```bash
php artisan queue:table
php artisan migrate
```

### **3. Cấu hình Redis (khuyến nghị):**

Trong file `.env`:
```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 📊 **Monitoring Jobs**

### **1. Xem danh sách jobs:**
```bash
# Xem jobs đang chờ
php artisan queue:monitor

# Xem failed jobs
php artisan queue:failed
```

### **2. Retry failed jobs:**
```bash
# Retry tất cả failed jobs
php artisan queue:retry all

# Retry job cụ thể
php artisan queue:retry {id}
```

### **3. Clear failed jobs:**
```bash
php artisan queue:flush
```

## 📝 **Logs**

Tất cả logs được ghi vào:
- `storage/logs/laravel.log`

### **Các loại log:**
- `Bắt đầu đồng bộ...` - Job bắt đầu
- `Đã dispatch job...` - Job được gửi vào queue
- `Đã tạo/cập nhật...` - Thành công
- `Lỗi...` - Có lỗi xảy ra

## ⚡ **Tối ưu hóa**

### **1. Rate Limiting**
- Jobs có delay ngẫu nhiên 1-5 giây để tránh rate limit của Shopify API

### **2. Queue Priority**
- Tất cả jobs sử dụng queue `shopify-sync`
- Có thể chạy nhiều workers cùng lúc

### **3. Error Handling**
- Jobs có retry mechanism
- Failed jobs được log chi tiết
- Có thể retry thủ công

## 🚨 **Lưu ý quan trọng**

1. **Luôn chạy queue worker** trước khi dispatch jobs
2. **Kiểm tra kết nối Shopify** trước khi sync
3. **Monitor logs** để theo dõi tiến trình
4. **Backup database** trước khi sync số lượng lớn
5. **Test với ít dữ liệu** trước khi sync toàn bộ

## 🔍 **Troubleshooting**

### **Jobs không chạy:**
```bash
# Kiểm tra queue worker
ps aux | grep queue:work

# Restart queue worker
php artisan queue:restart
```

### **Jobs bị failed:**
```bash
# Xem failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### **Timeout errors:**
- Tăng timeout trong job class
- Chia nhỏ batch size
- Kiểm tra network connection

## 📈 **Performance Tips**

1. **Sử dụng Redis** thay vì database queue
2. **Chạy multiple workers** cho queue `shopify-sync`
3. **Monitor memory usage** của queue workers
4. **Set up supervisor** để auto-restart workers
5. **Use job batching** cho large datasets 