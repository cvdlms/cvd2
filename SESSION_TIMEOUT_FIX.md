# ✅ KHẮC PHỤC: Vấn đề Giáo Viên Bị Logout Thường Xuyên

## Nguyên nhân:

### 1. **Session timeout quá ngắn: 1 giờ (3600s)**
- Giáo viên làm việc trên một trang (soạn đề, chấm bài) > 1 giờ không có request nào
- Server tự động xóa session → Bị logout

### 2. **Gọi session_start() nhiều lần**
- Một số file teacher gọi `session_start()` trước khi include `session_check.php`
- Gây xung đột và có thể reset session config

### 3. **PHP garbage collection mặc định**
- `session.gc_probability` mặc định rất thấp (1/1000)
- Session files có thể bị xóa ngay cả khi user còn hoạt động

### 4. **Không có cơ chế keep-alive**
- Khi giáo viên mở trang và làm việc offline (soạn đề), không có request nào gửi đến server
- Session timeout tính từ request cuối cùng → Dễ bị timeout

---

## Giải pháp đã áp dụng:

### ✅ 1. Tăng session timeout: 1 giờ → 8 giờ

**File:** `admin/system_config.json`
```json
"session_timeout": 28800  // Từ 3600 (1h) → 28800 (8h)
```

### ✅ 2. Cải thiện session configuration

**File:** `includes/session_check.php`

Thêm các cài đặt:
```php
ini_set('session.gc_maxlifetime', $sessionTimeout);     // 8 giờ
ini_set('session.cookie_lifetime', $sessionTimeout);    // Cookie tồn tại 8 giờ
ini_set('session.gc_probability', 1);                   // Tăng xác suất GC
ini_set('session.gc_divisor', 100);                     // 1/100 = 1% mỗi request
```

**Default thay đổi:** Từ 3600s (1h) → 28800s (8h)

### ✅ 3. Xóa session_start() trùng lặp

**Files đã sửa:**
- `teacher/lucky_wheel.php`
- `teacher/matrix.php`
- `teacher/matrix_builder.php`
- `teacher/practice.php`

**Trước:**
```php
<?php
session_start();
include '../includes/session_check.php';
```

**Sau:**
```php
<?php
include '../includes/session_check.php';  // Đã có session_start() bên trong
```

### ✅ 4. Thêm Auto Keep-Alive

**File mới:** `api/keep_alive.php`
- API đơn giản cập nhật `$_SESSION['LAST_ACTIVITY']`
- Trả về status để monitor

**Files đã sửa:**
- `includes/teacher_navbar.php`
- `admin/navbar.php`

**Cơ chế:**
```javascript
// Tự động gọi API mỗi 5 phút
setInterval(function() {
    fetch('../api/keep_alive.php')  // Cập nhật LAST_ACTIVITY
}, 300000);  // 5 phút = 300,000ms
```

**Lợi ích:**
- Giáo viên mở tab và làm việc offline vẫn giữ session
- Mỗi 5 phút tự động ping server
- 8 giờ / 5 phút = 96 lần ping → Session luôn fresh

---

## Kết quả:

| Trước | Sau |
|-------|-----|
| Timeout: 1 giờ | Timeout: 8 giờ |
| Không keep-alive | Auto keep-alive 5 phút/lần |
| Session_start() trùng lặp | Chỉ gọi 1 lần |
| GC không ổn định | GC được config rõ ràng |
| **Logout sau ~1 giờ** | **Có thể làm việc cả ngày không bị logout** |

---

## Lưu ý:

1. **Session timeout 8 giờ** phù hợp cho môi trường giáo dục (1 ca làm việc)
2. **Keep-alive 5 phút** đủ nhẹ, không ảnh hưởng hiệu suất
3. Nếu muốn điều chỉnh timeout, sửa trong `admin/system_config.json`
4. API keep_alive cần đặt ở navbar (load trên mọi trang)

---

## Test:

1. **Đăng nhập** với tài khoản giáo viên
2. **Mở trang soạn đề** hoặc bất kỳ trang nào
3. **Để tab mở** trong 1-2 giờ (hoặc longer)
4. **Quay lại** → Vẫn đăng nhập, không bị logout
5. **Kiểm tra Console** (F12) → Thấy keep-alive request mỗi 5 phút

---

## Theo dõi:

Nếu vẫn còn vấn đề, kiểm tra:
- Browser Console có lỗi fetch keep_alive không?
- Server logs có ghi lại session timeout không?
- Có extension hoặc antivirus block request không?
