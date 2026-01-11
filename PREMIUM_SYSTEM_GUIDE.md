# 📚 HƯỚNG DẪN HỆ THỐNG PREMIUM

## 📖 MỤC LỤC
1. [Giới thiệu](#giới-thiệu)
2. [Hướng dẫn cho Admin](#hướng-dẫn-cho-admin)
3. [Hướng dẫn cho Giáo viên](#hướng-dẫn-cho-giáo-viên)
4. [Tính năng Premium](#tính-năng-premium)
5. [Cấu hình hệ thống](#cấu-hình-hệ-thống)

---

## 🌟 GIỚI THIỆU

Hệ thống Premium CVD cung cấp các tính năng nâng cao cho giáo viên thông qua gói đăng ký trả phí.

### **Tính năng chính:**
- ✅ Tạo không giới hạn đề thi (Miễn phí: 10 đề)
- ✅ Import câu hỏi từ Excel (Chỉ Premium)
- ✅ Xuất đề thi kèm đáp án (Chỉ Premium)
- ✅ Tạo ma trận tự động (Chỉ Premium)
- ✅ Thống kê nâng cao (Chỉ Premium)

### **Gói Premium:**
| Gói | Thời hạn | Giá | Tiết kiệm |
|-----|----------|-----|-----------|
| **Cơ bản** | 1 tháng | 100,000 VNĐ | - |
| **Tiết kiệm** | 6 tháng | 500,000 VNĐ | 100,000 VNĐ |
| **Tốt nhất** | 12 tháng | 900,000 VNĐ | 300,000 VNĐ |

---

## 👨‍💼 HƯỚNG DẪN CHO ADMIN

### 1️⃣ **Truy cập trang quản lý Premium**
- **URL:** `http://localhost/cvd2/admin/premium_management.php`
- **Yêu cầu:** Đăng nhập với tài khoản Admin

### 2️⃣ **Quản lý Subscriptions (Đăng ký)**

#### **Xem danh sách:**
- Tab **"Đăng ký Premium"** hiển thị tất cả tài khoản đã kích hoạt
- Màu xanh: Còn hạn > 7 ngày
- Màu vàng: Sắp hết hạn (≤ 7 ngày)
- Màu xám: Đã hết hạn

#### **Gia hạn Premium:**
1. Click nút **"Gia hạn"** trên dòng tài khoản
2. Chọn số ngày gia hạn (30/180/365)
3. Click **"Gia hạn"**

#### **Thu hồi Premium:**
1. Click nút **"Thu hồi"** màu đỏ
2. Xác nhận thu hồi
3. Tài khoản sẽ bị hủy Premium ngay lập tức

### 3️⃣ **Tạo Key kích hoạt**

#### **Tạo key mới:**
1. Tab **"Quản lý Key"**
2. Chọn gói (1/6/12 tháng)
3. Nhập số lượng key cần tạo
4. Click **"Tạo Key"**

#### **Copy key:**
- Click icon 📋 để copy key
- Gửi key cho giáo viên qua email/tin nhắn

#### **Thu hồi key:**
- Click **"Thu hồi"** để vô hiệu hóa key chưa sử dụng

### 4️⃣ **Duyệt đơn đăng ký**

#### **Xem đơn chờ duyệt:**
- Tab **"Đơn đăng ký"**
- Đơn có màu vàng: Chờ duyệt
- Đơn có màu xanh: Đã duyệt
- Đơn có màu đỏ: Đã từ chối

#### **Duyệt đơn:**
1. Click **"Duyệt"** trên đơn chờ
2. Hệ thống tự động kích hoạt Premium
3. Giáo viên nhận thông báo

#### **Từ chối đơn:**
1. Click **"Từ chối"**
2. Nhập lý do từ chối
3. Giáo viên nhận thông báo

### 5️⃣ **Cấu hình hệ thống**

#### **Chọn Học kì mặc định:**
1. Tab **"Cấu hình hệ thống"**
2. Chọn **Học kì 1** hoặc **Học kì 2**
3. Click **"Lưu"**
4. Tất cả trang sẽ hiển thị dữ liệu theo học kì đã chọn

#### **Xem thống kê:**
- Tổng số tài khoản Premium
- Số tài khoản sắp hết hạn
- Key chưa sử dụng
- Đơn chờ duyệt

---

## 👨‍🏫 HƯỚNG DẪN CHO GIÁO VIÊN

### 1️⃣ **Kích hoạt Premium**

#### **Phương pháp 1: Sử dụng Key**
1. Truy cập **Premium** từ menu navbar
2. Tab **"Kích hoạt bằng Key"**
3. Nhập key 16 ký tự (VD: `ABCD-EFGH-IJKL-MNOP`)
4. Click **"Kích hoạt"**
5. Hệ thống xác nhận kích hoạt thành công

#### **Phương pháp 2: Đăng ký đợi duyệt**
1. Tab **"Đăng ký Premium"**
2. Điền thông tin:
   - Họ tên
   - Email
   - Số điện thoại
   - Chọn gói (1/6/12 tháng)
3. Nhập ghi chú (tùy chọn)
4. Click **"Gửi đơn đăng ký"**
5. Chờ Admin duyệt đơn

### 2️⃣ **Kiểm tra trạng thái Premium**

#### **Xem trên Navbar:**
- ⭐ **Premium** (màu vàng): Đã kích hoạt
- 🔒 **Nâng cấp Premium** (màu xám): Chưa kích hoạt
- Badge đỏ với số ngày: Sắp hết hạn

#### **Xem chi tiết:**
- Click vào badge Premium trên navbar
- Xem ngày hết hạn
- Xem số ngày còn lại

### 3️⃣ **Sử dụng tính năng Premium**

#### **Tạo không giới hạn đề thi:**
- Tài khoản miễn phí: **10 đề tối đa**
- Tài khoản Premium: **Không giới hạn**

#### **Import câu hỏi từ Excel:**
1. Vào **Ngân hàng câu hỏi**
2. Click **"📥 Import từ Excel"**
3. Tải file mẫu Excel
4. Điền câu hỏi theo định dạng
5. Upload file và import

#### **Xuất đề thi kèm đáp án:**
- Chỉ tài khoản Premium mới xem nút **"Xuất kèm đáp án"**

### 4️⃣ **Gia hạn Premium**

#### **Khi sắp hết hạn:**
- Hệ thống hiển thị cảnh báo khi còn ≤ 7 ngày
- Liên hệ Admin để:
  - Nhận key gia hạn mới
  - Hoặc gửi đơn đăng ký gia hạn

---

## 🎯 TÍNH NĂNG PREMIUM

### **So sánh Miễn phí vs Premium**

| Tính năng | Miễn phí | Premium |
|-----------|----------|---------|
| Tạo đề thi | 10 đề | ∞ Không giới hạn |
| Import Excel | ❌ | ✅ |
| Xuất kèm đáp án | ❌ | ✅ |
| Ma trận tự động | ❌ | ✅ |
| Thống kê nâng cao | ❌ | ✅ |
| Hỗ trợ ưu tiên | ❌ | ✅ |

### **Tính năng đang phát triển:**
- 🔜 Tạo đề từ AI
- 🔜 Phân tích kết quả học sinh
- 🔜 Thống kê theo chủ đề
- 🔜 Đề thi đa dạng (tự luận, điền khuyết)

---

## ⚙️ CẤU HÌNH HỆ THỐNG

### **File cấu hình chính:**

#### `admin/system_config.json`
```json
{
  "current_semester": "hk2",
  "premium_features_enabled": true
}
```

#### `admin/premium_packages.json`
```json
{
  "1": {"name": "Gói Cơ bản", "duration_days": 30, "price": 100000},
  "2": {"name": "Gói Tiết kiệm", "duration_days": 180, "price": 500000},
  "3": {"name": "Gói Tốt nhất", "duration_days": 365, "price": 900000}
}
```

### **Cấu trúc file dữ liệu:**

#### `premium_keys.json`
```json
[
  {
    "key": "XXXX-XXXX-XXXX-XXXX",
    "package_id": 3,
    "created_at": "2025-01-20 10:00:00",
    "used": false,
    "used_by": null,
    "used_at": null
  }
]
```

#### `premium_subscriptions.json`
```json
[
  {
    "username": "teacher1",
    "package_id": 3,
    "activated_at": "2025-01-20 10:00:00",
    "expires_at": "2026-01-20 10:00:00",
    "status": "active"
  }
]
```

#### `premium_orders.json`
```json
[
  {
    "id": 1,
    "username": "teacher2",
    "fullname": "Nguyễn Văn A",
    "email": "teacher@school.edu.vn",
    "phone": "0123456789",
    "package_id": 2,
    "notes": "Ghi chú",
    "submitted_at": "2025-01-20 11:00:00",
    "status": "pending",
    "reviewed_by": null,
    "reviewed_at": null,
    "admin_note": null
  }
]
```

---

## 🔧 XỬ LÝ SỰ CỐ

### **Lỗi thường gặp:**

#### **Key không hợp lệ:**
- Kiểm tra key có đúng 16 ký tự
- Kiểm tra key đã được tạo trong hệ thống
- Kiểm tra key chưa bị thu hồi

#### **Không thể kích hoạt:**
- Kiểm tra file `premium_keys.json` có tồn tại
- Kiểm tra quyền ghi file (chmod 755)
- Kiểm tra log trong `logs/premium.log`

#### **Premium hết hạn sớm:**
- Chạy script `checkExpiredSubscriptions()` thủ công
- Kiểm tra múi giờ server

### **Backup dữ liệu:**
```bash
# Backup tất cả file Premium
cp admin/premium_*.json admin/backup/
cp admin/system_config.json admin/backup/
```

---

## 📞 HỖ TRỢ

**Liên hệ Admin hệ thống:**
- 📧 Email: admin@school.edu.vn
- 📱 Hotline: 0123-456-789
- 🕐 Giờ làm việc: 8:00 - 17:00 (T2-T6)

**Tài liệu kỹ thuật:**
- Xem file `PREMIUM_SYSTEM_DOCUMENTATION.md`
- Xem code trong `includes/premium_helper.php`

---

© 2025 CVD Learning Management System
