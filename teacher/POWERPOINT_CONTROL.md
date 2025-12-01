# PowerPoint Remote Control System

## Hướng Dẫn Nhanh Cho Giáo Viên (Dễ Nhất)

### ✅ 3 Bước Đơn Giản:

**Bước 1: Cài Đặt AutoHotkey (1 lần)**
1. Tải từ: https://www.autohotkey.com/
2. Download **AutoHotkey v1.1** (stable)
3. Cài đặt như bình thường

**Bước 2: Chạy Script Điều Khiển**
1. Vào thư mục `cvd2\teacher\`
2. **Double-click** file: `start_ppt_control_ahk.bat`
3. Một cửa sổ Command sẽ hiện lên

**Bước 3: Điều Khiển Từ Điện Thoại**
1. Mở PowerPoint và mở presentation
2. Trên điện thoại, mở Camera hoặc app Scan QR
3. Truy cập: `http://psmcvn.com/cvd2/teacher/remote_control.php` (hoặc scan QR)
4. Nhấn các nút để điều khiển PowerPoint:
   - 🎬 **Trình chiếu** → Bắt đầu slideshow (F5)
   - ⏹️ **Dừng chiếu** → Dừng slideshow (ESC)
   - ⬅️ **Slide trước** → Slide trước đó (Left Arrow)
   - ➡️ **Slide sau** → Slide tiếp theo (Space)

**Để Dừng:**
- Nhấn **Ctrl + Alt + Z** trên máy tính
- Hoặc đóng cửa sổ Command

---

## Cài Đặt Chi Tiết (Cho Kỹ Thuật Viên / Developer)

### Yêu Cầu Hệ Thống
- Windows 10/11
- PowerPoint (2016 trở lên)
- XAMPP đang chạy (server PHP)
- AutoHotkey v1.1 (optional - nếu dùng script .ahk)

### Phương Án 1: Sử Dụng AutoHotkey Script (Khuyến Nghị) ⭐

#### Cài Đặt AutoHotkey:
1. Tải từ: https://www.autohotkey.com/download/
2. Cài **AutoHotkey v1.1** (không phải v2.0)
3. Đảm bảo AutoHotkey được thêm vào PATH

#### Chạy Script:
```powershell
cd C:\xampp\htdocs\cvd2\teacher
.\start_ppt_control_ahk.bat
```

**Ưu điểm:** 
- ✅ Đơn giản hơn Python
- ✅ Không cần build exe
- ✅ Tương thích tốt với PowerPoint
- ✅ Script text dễ tùy chỉnh

### Phương Án 2: Sử Dụng File .EXE (Python)

#### Cho Người Build (Chỉ cần làm 1 lần):
Trên máy có cài Python:

```powershell
cd C:\xampp\htdocs\cvd2\teacher
.\build_exe.bat
```

Kết quả: File `ppt_controller.exe` sẽ được tạo trong thư mục `dist\`

#### Phân Phối Cho Giáo Viên:
1. Sao chép file `ppt_controller.exe` qua USB hoặc chia sẻ qua mạng
2. Giáo viên chỉ cần double-click file để chạy (không cần cài Python)

### Phương Án 2: Sử Dụng Python Script (Nếu Giáo Viên Có Python)

#### Cài Đặt Python (Một lần):
1. Tải Python từ https://www.python.org/downloads/
2. **QUAN TRỌNG**: Khi cài, chọn ✓ "Add Python to PATH"
3. Mở PowerShell và chạy:
```powershell
python -m pip install pyautogui
```

#### Chạy Controller:
```powershell
cd C:\xampp\htdocs\cvd2\teacher
python powerpoint_controller.py
```

Hoặc double-click `start_powerpoint_controller.bat` trong thư mục `cvd2/teacher`

---

## Các Lệnh Điều Khiển

## Các Lệnh Điều Khiển

| Nút / Lệnh | Phím | Mô Tả |
|-----------|------|-------|
| 🎬 Trình chiếu | F5 | Bắt đầu slideshow |
| ⏹️ Dừng chiếu | ESC | Dừng slideshow |
| ⬅️ Slide trước | Left Arrow | Slide trước đó |
| ➡️ Slide sau | Space / Right Arrow | Slide tiếp theo |

---

## Xử Lý Sự Cố

### ❌ Vấn Đề: Không Tìm Thấy Nút Tải File

**Giải pháp**: 
- File `.exe` chỉ xuất hiện nếu đã build bằng `build_exe.bat`
- Nếu không thấy nút, hãy liên hệ kỹ thuật viên để build file

### ❌ Vấn Đề: Tải File Nhưng Không Chạy Được

**Giải pháp**:
1. Kiểm tra Windows Defender / Antivirus có block file không
   - Nếu có cảnh báo, chọn "Allow" hoặc "Run anyway"
2. Đảm bảo có quyền lập trình viên (Admin) để chạy file
3. Kiểm tra xem PowerPoint có được cài đúng không

### ❌ Vấn Đề: Chạy File Nhưng Lệnh Không Thực Hiện

**Giải pháp**:
1. **QUAN TRỌNG**: Phải mở PowerPoint và **click vào cửa sổ PowerPoint** để nó được focus
2. Đảm bảo terminal của controller vẫn đang chạy (không bị đóng)
3. Kiểm tra file lệnh được tạo:
   - Mở: `C:\xampp\htdocs\cvd2\data\remote_control\`
   - Sẽ thấy file `.json` với tên session

### ❌ Vấn Đề: Điện Thoại Không Truy Cập Được

**Giải pháp**:
1. Đảm bảo máy tính và điện thoại cùng WiFi
2. Thay vì localhost, sử dụng IP LAN:
   - Trên máy tính: Start → Settings → Network → Status → IP Address (tìm IPv4)
   - Nhập vào URL: `http://<IP_LAN>/cvd2/teacher/remote_control.php`
3. Kiểm tra firewall cho phép cổng 80 (HTTP)

### ❌ Vấn Đề: Python / pyautogui Error

**Giải pháp**:
1. Cài lại Python từ https://www.python.org
2. **Chắc chắn** chọn "Add Python to PATH"
3. Mở PowerShell mới và chạy:
```powershell
python -m pip install --upgrade pyautogui
```

---

## Kiến Trúc Hệ Thống

```
Điện Thoại (Browser)
    ↓ (gửi lệnh qua HTTP API)
XAMPP Server (PHP API)
    ↓ (lưu lệnh vào file JSON)
Thư Mục: data/remote_control/
    ↓ (đọc file lệnh)
PowerPoint Controller (Python/EXE)
    ↓ (gửi phím/di chuột)
PowerPoint
```

### Flow Chi Tiết:
1. **Điện Thoại** nhấn nút → gọi `api/remote_commands.php`
2. **API PHP** lưu lệnh vào file JSON (`data/remote_control/{session_id}_commands.json`)
3. **Controller** (chạy trên máy tính) liên tục poll file lệnh
4. **Controller** đọc được lệnh → gửi phím/di chuột tới PowerPoint
5. **PowerPoint** nhận phím → thực hiện hành động (next slide, fullscreen, etc.)

---

## Bảo Mật & Lưu Ý

⚠️ **Lưu ý quan trọng**:
- Hệ thống này dùng cho **LAN nội bộ** (không nên expose ra internet công cộng)
- Session ID được sinh ngẫu nhiên để tránh truy cập không được phép
- File lệnh được lưu trong thư mục `data/remote_control/` không công khai
- **Nếu expose ra internet**: Nên thêm authentication (đăng nhập)

⚠️ **Yêu Cầu Quyền**:
- `pyautogui` cần quyền để điều khiển bàn phím/chuột
- Một số phần mềm bảo mật có thể cảnh báo → cần cho phép

---

## Lệnh Nâng Cao (Cho Kỹ Thuật Viên)

### Build Executable Từ Scratch

```powershell
# 1. Cài dependencies
python -m pip install pyinstaller pyautogui

# 2. Build file exe (single file, no console)
cd C:\xampp\htdocs\cvd2\teacher
pyinstaller --noconsole --onefile --name ppt_controller powerpoint_controller.py

# 3. Kết quả sẽ ở: dist\ppt_controller.exe
```

### Tạo Scheduled Task (Chạy Tự Động Khi Đăng Nhập)

```powershell
# 1. Tạo task chạy tự động khi user đăng nhập
$action = New-ScheduledTaskAction -Execute "C:\xampp\htdocs\cvd2\teacher\dist\ppt_controller.exe"
$trigger = New-ScheduledTaskTrigger -AtLogOn
Register-ScheduledTask -Action $action -Trigger $trigger -TaskName "PowerPoint Remote Controller" -Description "Auto-start PowerPoint controller" -RunLevel Highest

# 2. Để xóa task:
Unregister-ScheduledTask -TaskName "PowerPoint Remote Controller" -Confirm:$false
```

### Kiểm Tra File Lệnh Được Tạo

```powershell
# Xem các session hiện tại
Get-ChildItem "C:\xampp\htdocs\cvd2\data\remote_control\"

# Xem nội dung file lệnh (thay SESSION_ID bằng thực tế)
Get-Content "C:\xampp\htdocs\cvd2\data\remote_control\SESSION_ID_commands.json" | ConvertFrom-Json | Format-List
```

### API Endpoints

**Gửi lệnh từ điện thoại:**
```bash
POST /cvd2/teacher/api/remote_commands.php
Content-Type: application/json

{
    "session": "SESSION_ID",
    "command": "next_slide"
}
```

**Đọc lệnh (controller):**
```bash
GET /cvd2/teacher/api/remote_commands.php?session=SESSION_ID
```

**Gửi status ACK:**
```bash
POST /cvd2/teacher/api/remote_status.php
Content-Type: application/json

{
    "session": "SESSION_ID",
    "status": "success",
    "message": "Slide 5 of 10"
}
```

---

## Thư Mục & File Quan Trọng

```
cvd2/teacher/
├── remote_control.php              ← Giao diện teacher (QR code, download button)
├── remote_mobile.php               ← Giao diện mobile (nút điều khiển)
├── powerpoint_controller.py        ← Script Python chính (điều khiển PowerPoint)
├── start_powerpoint_controller.bat ← File batch để chạy (double-click)
├── build_exe.bat                   ← Build executable (chỉ kỹ thuật viên)
├── requirements.txt                ← Dependencies Python
├── POWERPOINT_CONTROL.md           ← Tài liệu này
├── dist/
│   └── ppt_controller.exe          ← Executable (sau khi build)
├── api/
│   ├── remote_commands.php         ← Lưu/đọc lệnh
│   ├── remote_status.php           ← Status feedback
│   └── download_exe.php            ← Download link exe
└── data/remote_control/
    └── *.json                      ← File lệnh session
```

---

## Hỗ Trợ & Liên Hệ

Nếu gặp vấn đề:
1. Đọc phần **"Xử Lý Sự Cố"** ở trên
2. Kiểm tra console/terminal có thông báo lỗi gì không
3. Liên hệ kỹ thuật viên hoặc người quản trị hệ thống

---

**Tạo ngày**: 2025-12-01  
**Phiên bản**: 2.0 (Hỗ Trợ Executable)  
**Cập nhật lần cuối**: 2025-12-01
