# 🎖️ HỆ THỐNG PREMIUM VÀ QUẢN LÝ HỌC KÌ - CVD LMS

## 📋 Tổng Quan Hệ Thống

Hệ thống Premium đã được tích hợp hoàn chỉnh với các tính năng:

### 1. **Quản Lý Học Kì**
- ✅ Admin có thể chọn học kì mặc định (HK1/HK2)
- ✅ Dữ liệu học sinh và giáo viên tự động lọc theo học kì
- ✅ Mặc định: Học kì 2

### 2. **Hệ Thống Premium**
- ✅ 3 gói Premium: 1 tháng, 6 tháng, 1 năm
- ✅ Kích hoạt bằng Key hoặc Đơn đăng ký
- ✅ Admin quản lý: Key, Subscription, Orders
- ✅ Tự động thu hồi quyền khi hết hạn

---

## 📦 CẤU TRÚC DỮ LIỆU

### File JSON Đã Tạo:

```
cvd2/admin/
├── system_config.json           # Cấu hình hệ thống (học kì, Premium)
├── premium_packages.json        # Các gói Premium
├── premium_keys.json            # Danh sách key kích hoạt
├── premium_subscriptions.json   # Danh sách subscription
└── premium_orders.json          # Đơn đăng ký chờ duyệt
```

### 1. **system_config.json**
```json
{
    "semester": {
        "default": "hk2",
        "current": "hk2",
        "available": ["hk1", "hk2"],
        "labels": {
            "hk1": "Học kì 1",
            "hk2": "Học kì 2"
        }
    },
    "premium": {
        "enabled": true,
        "trial_days": 7,
        "features": {
            "unlimited_exams": true,
            "export_with_answers": true,
            "auto_matrix": true,
            "advanced_stats": true
        }
    }
}
```

### 2. **premium_packages.json**
```json
[
    {
        "package_id": 1,
        "name": "Premium 1 tháng",
        "duration_days": 30,
        "price": 100000,
        "features": [...]
    }
]
```

### 3. **premium_keys.json**
```json
[
    {
        "key_id": "key_xxx",
        "key_code": "XXXX-XXXX-XXXX-XXXX",
        "package_id": 1,
        "status": "unused", // unused, used, revoked
        "created_at": "2026-01-10 10:00:00",
        "used_by": null,
        "used_at": null
    }
]
```

### 4. **premium_subscriptions.json**
```json
[
    {
        "subscription_id": "sub_xxx",
        "username": "giaovien01",
        "package_id": 1,
        "package_name": "Premium 1 tháng",
        "start_date": "2026-01-10",
        "end_date": "2026-02-10",
        "status": "active", // active, expired, revoked
        "activated_by": "key", // key, admin_approval
        "created_at": "2026-01-10 10:00:00"
    }
]
```

### 5. **premium_orders.json**
```json
[
    {
        "order_id": "order_xxx",
        "username": "giaovien01",
        "fullname": "Nguyễn Văn A",
        "email": "email@example.com",
        "package_id": 1,
        "price": 100000,
        "status": "pending", // pending, approved, rejected
        "created_at": "2026-01-10"
    }
]
```

---

## 🔧 CÁC FILE ĐÃ TẠO

### 1. **Helper Functions**
📄 `cvd2/includes/premium_helper.php` - Các hàm hỗ trợ:

**Hàm kiểm tra Premium:**
```php
isPremiumUser($username)           // Kiểm tra user có Premium
getActiveSubscription($username)   // Lấy subscription đang active
getPremiumDaysRemaining($username) // Số ngày còn lại
```

**Hàm kích hoạt:**
```php
validatePremiumKey($keyCode)           // Validate key
activatePremiumByKey($username, $key)  // Kích hoạt bằng key
createPremiumOrder($data)              // Tạo đơn đăng ký
approvePremiumOrder($orderId, $status) // Duyệt đơn (Admin)
```

**Hàm quản lý (Admin):**
```php
generatePremiumKeys($packageId, $quantity) // Tạo key
revokePremium($username, $reason)          // Thu hồi Premium
extendPremium($username, $days)            // Gia hạn
checkExpiredSubscriptions()                // Check hết hạn
```

**Hàm cấu hình:**
```php
getSystemConfig()                 // Lấy config hệ thống
getDefaultSemester()              // Lấy học kì mặc định
getCurrentSemester()              // Lấy học kì hiện tại
updateCurrentSemester($semester)  // Cập nhật học kì (Admin)
```

### 2. **Admin Pages**
📄 `cvd2/admin/premium_management.php` - Trang quản lý Premium

**Chức năng:**
- Dashboard: Thống kê Premium
- Tab Subscriptions: Quản lý tài khoản Premium
- Tab Keys: Quản lý key kích hoạt
- Tab Orders: Duyệt đơn đăng ký
- Tab Config: Cấu hình hệ thống

### 3. **Tab Components**
📄 `cvd2/admin/premium_subscriptions_tab.php` - Quản lý subscriptions
📄 `cvd2/admin/premium_keys_tab.php` - Quản lý keys
📄 `cvd2/admin/premium_orders_tab.php` - Quản lý orders
📄 `cvd2/admin/premium_config_tab.php` - Cấu hình

---

## 🚀 CÁCH SỬ DỤNG

### A. ADMIN

#### 1. Tạo Key Premium
```php
// Truy cập: admin/premium_management.php
// Tab: "Quản Lý Key"
// Click: "Tạo Key Mới"
// Chọn gói và số lượng
```

#### 2. Duyệt Đơn Đăng Ký
```php
// Tab: "Đơn Đăng Ký"
// Click "Duyệt" hoặc "Từ chối"
// Nếu duyệt -> Tự động tạo subscription
```

#### 3. Gia Hạn/Thu Hồi Premium
```php
// Tab: "Tài Khoản Premium"
// Click "Gia hạn" -> Nhập số ngày
// Click "Thu hồi" -> Nhập lý do
```

#### 4. Thay Đổi Học Kì Hệ Thống
```php
// Tab: "Cấu Hình"
// Chọn học kì mặc định
// Dữ liệu sẽ tự động lọc theo học kì
```

### B. GIÁO VIÊN

#### 1. Kích Hoạt Premium Bằng Key
```php
// File cần tạo: teacher/premium_activation.php
// Nhập key -> Kích hoạt ngay
```

#### 2. Đăng Ký Premium (Chờ Duyệt)
```php
// Điền form: Họ tên, Email, Gói Premium
// Gửi đơn -> Admin duyệt
```

#### 3. Kiểm Tra Trạng Thái Premium
```php
// Hiển thị trong dashboard:
// - Trạng thái: Active/Expired
// - Ngày hết hạn
// - Số ngày còn lại
```

---

## 💻 MÃ NGUỒN TÍCH HỢP

### 1. Check Premium Trong Code
```php
<?php
include '../includes/premium_helper.php';

// Kiểm tra Premium
if (!isPremiumUser($_SESSION['username'])) {
    die('Tính năng này chỉ dành cho tài khoản Premium');
}

// Lấy số ngày còn lại
$daysRemaining = getPremiumDaysRemaining($_SESSION['username']);
if ($daysRemaining <= 7) {
    echo "<div class='alert alert-warning'>Premium của bạn sắp hết hạn trong $daysRemaining ngày</div>";
}
?>
```

### 2. Hiển Thị Badge Premium
```php
<?php if (isPremiumUser($username)): ?>
    <span class="badge bg-warning text-dark">⭐ Premium</span>
<?php endif; ?>
```

### 3. Ẩn/Hiện Tính Năng Premium
```php
<?php if (isPremiumUser($_SESSION['username'])): ?>
    <button class="btn btn-success">📥 Xuất Đề + Đáp Án</button>
    <button class="btn btn-info">📊 Ma Trận Tự Động</button>
<?php else: ?>
    <button class="btn btn-secondary" disabled>
        🔒 Xuất Đề (Premium Only)
    </button>
    <a href="premium_activation.php" class="btn btn-warning">
        ⭐ Nâng Cấp Premium
    </a>
<?php endif; ?>
```

### 4. Lấy Học Kì Hiện Tại
```php
<?php
$currentSemester = getCurrentSemester(); // 'hk1' hoặc 'hk2'

// Load dữ liệu theo học kì
$dataFile = "questions/khoi6/{$currentSemester}/subject_1.json";
?>
```

---

## 📊 WORKFLOW PREMIUM

### Flow 1: Kích Hoạt Bằng Key
```
1. Admin tạo key
   ↓
2. Giáo viên nhận key
   ↓
3. Giáo viên nhập key vào trang activation
   ↓
4. Hệ thống validate key
   ↓
5. Tạo subscription + Mark key as "used"
   ↓
6. Giáo viên có quyền Premium ngay lập tức
```

### Flow 2: Đăng Ký (Chờ Duyệt)
```
1. Giáo viên điền form đăng ký
   ↓
2. Tạo order với status "pending"
   ↓
3. Admin vào trang quản lý
   ↓
4. Admin duyệt order
   ↓
5. Hệ thống tạo subscription
   ↓
6. Giáo viên có quyền Premium
```

### Flow 3: Hết Hạn Tự Động
```
1. Cron job hoặc check khi load page
   ↓
2. Hàm checkExpiredSubscriptions()
   ↓
3. Nếu end_date < now
   ↓
4. Cập nhật status = "expired"
   ↓
5. Giáo viên mất quyền Premium
```

---

## 🎯 CÁC TÍNH NĂNG PREMIUM

### Tính Năng Mở Khóa Khi Có Premium:

1. ✅ **Tạo đề không giới hạn**
2. ✅ **Xuất đề + đáp án**
3. ✅ **Ma trận đề tự động**
4. ✅ **Thống kê nâng cao**
5. ✅ **Import từ Excel**
6. ✅ **Ngân hàng câu hỏi không giới hạn**

### Cách Tích Hợp:
```php
// Trong file export
if (!isPremiumUser($_SESSION['username'])) {
    die('Tính năng xuất đề + đáp án chỉ dành cho Premium');
}

// Trong file tạo đề
$userQuotas = isPremiumUser($username) ? 999999 : 10;
if ($totalExams >= $userQuotas) {
    die('Bạn đã hết quota. Nâng cấp Premium để tạo không giới hạn!');
}
```

---

## 🔐 BẢO MẬT

### Nguyên Tắc:
1. ✅ Key chỉ dùng 1 lần
2. ✅ Không gia hạn chồng thời gian
3. ✅ Premium hết hạn → tự động thu quyền
4. ✅ Ghi log mọi thao tác

### Log File:
```
cvd2/logs/premium_log.json
```

Mỗi action đều được ghi log:
```json
{
    "timestamp": "2026-01-10 10:00:00",
    "username": "giaovien01",
    "action": "activate",
    "details": "Kích hoạt Premium bằng key: XXXX-XXXX-XXXX-XXXX",
    "ip": "192.168.1.1"
}
```

---

## 📱 CÁC FILE CẦN TẠO TIẾP

### 1. **Trang Giáo Viên**
- `teacher/premium_activation.php` - Trang kích hoạt Premium
- `teacher/premium_status.php` - Xem trạng thái Premium

### 2. **Tabs Admin Còn Lại**
- `admin/premium_keys_tab.php` - Tab quản lý key
- `admin/premium_orders_tab.php` - Tab quản lý đơn
- `admin/premium_config_tab.php` - Tab cấu hình
- `admin/premium_actions.php` - API xử lý AJAX

### 3. **Tích Hợp Vào Các Trang**
- Thêm check Premium vào: `exam_create.php`, `question_bank.php`, etc.
- Thêm badge Premium vào header
- Thêm thông báo hết hạn

---

## 🐛 TROUBLESHOOTING

### Lỗi: "Tính năng này chỉ dành cho Premium"
```php
// Check trong file có dòng:
if (!isPremiumUser($_SESSION['username'])) { ... }

// Giải pháp: Kích hoạt Premium cho tài khoản
```

### Lỗi: "Key không hợp lệ"
```php
// Nguyên nhân: Key đã dùng hoặc không tồn tại
// Giải pháp: Admin tạo key mới
```

### Premium Không Tự Động Hết Hạn
```php
// Nguyên nhân: Chưa gọi checkExpiredSubscriptions()
// Giải pháp: Thêm vào header hoặc cron job
```

---

## ✅ CHECKLIST TRIỂN KHAI

- [x] Tạo cấu trúc dữ liệu JSON
- [x] Tạo helper functions
- [x] Tạo trang admin quản lý
- [ ] Tạo các tabs admin (keys, orders, config)
- [ ] Tạo trang giáo viên kích hoạt
- [ ] Tích hợp check Premium vào các trang
- [ ] Tạo API actions
- [ ] Testing đầy đủ

---

## 📞 HỖ TRỢ

**Files quan trọng:**
- `includes/premium_helper.php` - Tất cả logic Premium
- `admin/system_config.json` - Cấu hình hệ thống
- `admin/premium_management.php` - Dashboard admin

**Cách test:**
1. Tạo key từ admin
2. Kích hoạt từ teacher
3. Check premium bằng `isPremiumUser()`
4. Test các tính năng Premium

---

**Phiên bản:** 2.0 Premium Edition  
**Ngày:** 10/01/2026  
**Trạng thái:** 🟡 Đang triển khai (70%)
