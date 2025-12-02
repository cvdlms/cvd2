# PowerPoint Remote Control - Hướng Dẫn Giáo Viên

## 🎯 Mục Đích
Điều khiển PowerPoint từ **điện thoại** trong phòng học mà không cần dây điều khiển. Chỉ cần:
- 1 chuột ảo (touchpad) để trỏ
- 4 nút: Bắt đầu/Dừng chiếu, Slide trước/sau
- Kết nối Wi-Fi đơn giản

---

## ⚡ Khởi Động Nhanh (3 Bước)

### Bước 1: Tải & Chuẩn Bị
1. Tải file `run_local_server.bat` về máy tính
2. Yêu cầu: **Python 3.8+** (tải từ https://www.python.org)
   - Khi cài Python, **nhớ chọn "Add Python to PATH"**

### Bước 2: Chạy Server (Double-Click)
- Double-click file `run_local_server.bat`
- Chương trình sẽ:
  - ✅ Cài đặt tự động các thư viện cần thiết
  - ✅ Khởi động server
  - ✅ Mở trình duyệt trên máy tính
  - ✅ Tạo QR code để điện thoại quét

### Bước 3: Quét QR từ Điện Thoại
- Mở **ứng dụng Camera** trên điện thoại
- Quét **QR code** hiện trên màn hình máy tính
- Hoặc dán link thủ công: `http://<IP_MÁY>:5000/?token=socketio123`
  - IP máy sẽ được hiển thị trong cửa sổ server

---

## 🎮 Cách Sử Dụng

### Giao Diện Điều Khiển (Trên Điện Thoại)

#### 1️⃣ Touchpad (Diện Tích Lớn Trên Cùng)
- **Vuốt**: Di chuyển chuột
- **Nhấn lâu**: Click chuột
- **Nhấn 2 lần nhanh**: Double-click
- **Khóa nút**: Nút "Khóa Pad" để tránh vuốt vô tình

#### 2️⃣ Bốn Nút Điều Khiển PowerPoint

| Nút | Lệnh | Phím Tương Ứng |
|-----|------|---|
| **▶ Trình Chiếu** | Bắt đầu chiếu | F5 |
| **⏹ Dừng Chiếu** | Dừng chiếu, về desktop | ESC |
| **◀ Slide Trước** | Slide trước đó | ← (mũi tên trái) |
| **▶ Slide Sau** | Slide tiếp theo | Space |

#### 3️⃣ Nút Mouse (Dưới Touchpad)
- **Click Trái**: Bấm chuột trái
- **Click Phải**: Bấm chuột phải (menu bối cảnh)
- **Double Click**: Bấm đôi
- **Khóa/Unlock Pad**: Vô hiệu hóa touchpad tạm thời

---

## 🔧 Yêu Cầu & Chuẩn Bị

### Trên Máy Tính Giáo Viên
- ✅ Windows 7, 8, 10, 11 (hoặc Linux/Mac)
- ✅ Python 3.8+ (tải từ https://www.python.org)
- ✅ Kết nối Internet (để cài đặt thư viện lần đầu)

### Trên Điện Thoại
- ✅ iPhone hoặc Android
- ✅ Kết nối cùng Wi-Fi với máy tính
- ✅ Trình duyệt web (Safari, Chrome, Firefox, v.v.)

### Phòng Học
- ✅ Wi-Fi phòng học hoặc hotspot điện thoại từ giáo viên

---

## 📱 Kết Nối Từ Điện Thoại

### Cách 1: Quét QR Code (Dễ Nhất)
1. Mở **ứng dụng Camera** trên điện thoại
2. Hướng camera vào **QR code** trên màn hình máy tính
3. Nhấn vào thông báo xuất hiện ➜ **Mở đường link**
4. Chấp nhận, và sẵn sàng sử dụng!

### Cách 2: Nhập Link Thủ Công
1. Tìm **IP máy tính** từ cửa sổ server (ví dụ: `192.168.1.50`)
2. Trên điện thoại, mở trình duyệt (Chrome, Safari, v.v.)
3. Gõ vào thanh địa chỉ:
   ```
   http://192.168.1.50:5000/?token=socketio123
   ```
4. Bấm Enter và sẵn sàng!

---

## 🚀 Bước Chạy Chi Tiết

### Lần Đầu Chạy (Lâu Hơn ~2-3 Phút)
```
Double-click run_local_server.bat
  ↓
Chờ cài đặt thư viện...
  ↓
Server khởi động ✓
  ↓
Trình duyệt mở tại http://localhost:5000
  ↓
QR code được tạo
  ↓
Điện thoại quét ➜ Sẵn sàng điều khiển!
```

### Các Lần Chạy Sau (Nhanh ~10 Giây)
```
Double-click run_local_server.bat
  ↓
Server khởi động ngay lập tức
  ↓
Điện thoại kết nối
  ↓
Điều khiển PowerPoint!
```

---

## 🐛 Xử Lý Sự Cố

### ❌ "Python không được tìm thấy"
**Nguyên nhân**: Python chưa cài hoặc chưa thêm vào PATH

**Cách Sửa**:
1. Tải Python từ https://www.python.org
2. **Chọn phiên bản 3.8+** (ví dụ: 3.11 hoặc 3.12)
3. Khi cài, **đánh dấu ☑ "Add Python to PATH"**
4. Hoàn thành cài đặt
5. Khởi động lại máy tính
6. Thử lại `run_local_server.bat`

### ❌ "Port 5000 đang được sử dụng"
**Nguyên nhân**: Một chương trình khác đang dùng port 5000

**Cách Sửa**:
1. Đóng `run_local_server.bat` nếu còn chạy
2. Chờ 30 giây
3. Chạy lại `run_local_server.bat`

Hoặc:
1. Mở PowerShell với quyền Admin
2. Gõ:
   ```powershell
   netstat -ano | findstr :5000
   ```
3. Ghi nhớ số PID (cột cuối)
4. Gõ:
   ```powershell
   taskkill /PID <PID> /F
   ```
5. Thử lại

### ❌ Điện Thoại Không Kết Nối Được
**Nguyên nhân**: Điện thoại không cùng Wi-Fi với máy tính

**Cách Sửa**:
1. Kiểm tra máy tính và điện thoại đang dùng **cùng Wi-Fi**
2. IP máy tính nên là `192.168.x.x` hoặc `10.x.x.x` (xem trong cửa sổ server)
3. Thử ping từ điện thoại (ứng dụng như "Ping" hoặc "Network Analyzer")
4. Nếu ping được ➜ Vấn đề ở web, thử refresh trang (Ctrl+R hoặc F5)
5. Nếu ping không được ➜ Kiểm tra firewall trên máy tính

### ❌ PowerPoint Không Phản Hồi
**Nguyên nhân**: PowerPoint chưa được khởi động hoặc mất focus

**Cách Sửa**:
1. Mở **PowerPoint** trên máy tính
2. Mở file bài thuyết trình
3. **Nhấn F5** trên keyboard máy (hoặc nút "Bắt đầu Chiếu" trên điện thoại)
4. Chắc chắn PowerPoint window đang được **focus** (nằm trên cùng)
5. Thử bấm "Slide Sau" từ điện thoại

### ❌ Touchpad Di Chuyển Chuột Chậm
**Nguyên nhân**: Độ nhạy chuột bị thấp

**Cách Sửa**:
1. Thử **vuốt nhanh hơn** trên touchpad
2. Hoặc chỉnh cài đặt chuột trên máy tính:
   - Windows: Cài đặt → Thiết bị → Chuột → Tốc độ con trỏ

---

## 💡 Mẹo & Thủ Thuật

### Mở PowerPoint Ở Chế Độ Toàn Màn Hình
1. Mở bài thuyết trình trong PowerPoint
2. Bấm **F5** để vào slideshow (hoặc nút "Trình Chiếu" từ điện thoại)
3. PowerPoint sẽ hiển thị toàn màn hình
4. Bây giờ bạn có thể điều khiển từ điện thoại!

### Sử Dụng Hotkey từ Điện Thoại
- Dù không có nút chuyên biệt nào, bạn vẫn có thể gõ văn bản từ điện thoại (khi focus vào text box)
- Hoặc dùng **touchpad** để click vào các element trên slide

### Để Mở Rộng Chức Năng
- Chỉnh sửa file `socketio_server.py` để thêm nút mới
- Hoặc liên hệ người hỗ trợ kỹ thuật

---

## 🔒 Bảo Mật

### Token Mặc Định
- `socketio123` — để đơn giản cho lần đầu
- **Lưu ý**: Chỉ dùng trong mạng nội bộ (phòng học hoặc Wi-Fi cá nhân)

### Cách Bảo Vệ:
1. Chỉ chia sẻ link QR trong phòng học
2. Tắt Wi-Fi công cộng khi không sử dụng
3. Kiểm tra firewall trên máy tính

---

## 📞 Liên Hệ Hỗ Trợ

Nếu gặp sự cố không giải quyết được:
1. Kiểm tra file log: mở cửa sổ `run_local_server.bat` và đọc thông báo lỗi
2. Ghi lại error message
3. Liên hệ với người quản lý hệ thống

---

## 📝 Bản Quyền & Giấy Phép

Phần mềm này được cung cấp cho mục đích giáo dục.
Tự do sử dụng, sửa chữa, nhân bản cho nhu cầu cá nhân.

---

**Chúc bạn sử dụng thành công! 🎉**
