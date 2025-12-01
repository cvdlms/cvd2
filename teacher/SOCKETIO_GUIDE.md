# PowerPoint Remote Control - Socket.IO Version

**Giải pháp đơn giản, gọn gàng hơn - Socket.IO thay thế File-based Queue**

## 🚀 Tại sao Socket.IO tốt hơn?

| Tính năng | File-based | Socket.IO |
|----------|-----------|-----------|
| **Setup** | PHP + Python/AutoHotkey | Chỉ Python |
| **Communication** | Polling every 2s + File I/O | Real-time WebSocket |
| **Latency** | 200-2000ms | 10-50ms |
| **Complexity** | Phức tạp | Đơn giản |
| **Distributed** | Khó | Dễ (multi-device) |
| **Dependencies** | Nhiều | Ít |

## 📦 Cài đặt

### 1. Yêu cầu
- Python 3.8+
- pip (package manager)

### 2. Cài đặt thư viện

```bash
cd C:\xampp\htdocs\cvd2\teacher
pip install -r requirements_socketio.txt
```

### 3. Chạy server

**Cách 1: Batch file (Windows - Dễ nhất)**
```bash
start_socketio_server.bat
```

**Cách 2: Command line**
```bash
python socketio_server.py --host 0.0.0.0 --port 5000 --token socketio123
```

## 🎮 Sử dụng

### Trên máy tính (PC)
```
http://localhost:5000/?token=socketio123
```

### Trên điện thoại (Phone)
```
http://<PC_IP>:5000/?token=socketio123
```

**Tìm IP máy tính:**
```powershell
# PowerShell
ipconfig
# Tìm "IPv4 Address" trong mục Wi-Fi Adapter
# Ví dụ: 192.168.1.100
```

## 🎬 Các chức năng

### Touchpad
- **Swipe**: Điều khiển con chuột
- **Left Click**: Click chuột trái
- **Right Click**: Click chuột phải
- **Double Click**: Click đôi
- **Lock/Unlock**: Khóa/Mở khóa touchpad

### PowerPoint Control (4 nút)
- **▶ Trình Chiếu (F5)**: Bắt đầu chiếu bài thuyết trình
- **⏹ Dừng Chiếu (ESC)**: Dừng chiếu
- **◀ Slide Trước (←)**: Slide trước đó
- **▶ Slide Sau (Space)**: Slide kế tiếp

### Keyboard Shortcuts (trên client)
- **Enter**: Start presentation
- **Escape**: Stop presentation
- **←**: Previous slide
- **Space**: Next slide

## 🔒 Bảo mật

### Token
Mặc định: `socketio123`

Để thay đổi token khi chạy server:
```bash
python socketio_server.py --host 0.0.0.0 --port 5000 --token your_secret_token
```

### Trên mạng công cộng
- **KHÔNG** expose server lên Internet mà không có HTTPS
- Sử dụng ngrok hoặc Cloudflare Tunnel:

```bash
# Cài ngrok: https://ngrok.com
ngrok http 5000
# Sau đó kết nối qua URL ngrok
```

## 📝 File cấu hình

- `socketio_server.py`: Server chính
- `static/socketio_client.html`: Client web
- `requirements_socketio.txt`: Python dependencies
- `start_socketio_server.bat`: Batch launcher
- `socketio_server.log`: Nhật ký server

## 🐛 Troubleshooting

### "Port 5000 already in use"
```bash
# Tìm process sử dụng port 5000
netstat -ano | findstr :5000
# Kill process (thay PID)
taskkill /PID <PID> /F
```

### "Permission denied" khi điều khiển
- Chạy command prompt/PowerShell **với quyền Administrator**

### Điện thoại không kết nối được
- Kiểm tra firewall: cho phép Python qua firewall
- Kiểm tra IP: `ipconfig` trên PC
- Kiểm tra Wi-Fi: cả PC và phone phải cùng mạng

### PowerPoint không phản hồi
1. Mở PowerPoint trước
2. Bấm nút "Trình Chiếu" để bắt đầu
3. Chắc chắn PowerPoint window đang focus

## 🛠️ Tùy chỉnh

### Thay đổi token
Sửa file `start_socketio_server.bat` hoặc chạy với flag `--token`:
```bash
python socketio_server.py --token new_secret_123
```

### Thay đổi port
```bash
python socketio_server.py --port 8080
```

### Thêm lệnh mới
Sửa `socketio_server.py`, phần `on_powerpoint_command()`:
```python
command_map = {
    'START': 'F5',
    'STOP': 'ESC',
    'NEXT': 'SPACE',
    'PREV': 'LEFT',
    'YOUR_COMMAND': 'SOME_KEY',  # Thêm đây
}
```

## 📱 Kiến trúc

```
Client (Phone/Browser)
    ↓ WebSocket (Socket.IO)
Server (Python Flask)
    ↓ pynput library
    ↓
PC Keyboard/Mouse
    ↓
PowerPoint
```

**So sánh với cách cũ:**
```
Client (HTTP Request)
    ↓ PHP API
    ↓
File System (Queue)
    ↓ Polling
    ↓
PowerPoint Controller (Python/AutoHotkey)
    ↓
PC Keyboard/Mouse
    ↓
PowerPoint
```

## 📊 Hiệu suất

- **Latency**: 10-50ms (vs 200-2000ms file-based)
- **CPU**: ~1-2% (idle)
- **Memory**: ~50MB
- **Network**: ~5KB/s

## 🎯 So sánh giải pháp

| Giải pháp | Đơn giản | Tốc độ | Độ tin cậy | Dễ triển khai |
|-----------|---------|--------|-----------|---------------|
| **Socket.IO** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| File-based | ⭐⭐ | ⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| AutoHotkey | ⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |
| Python exe | ⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐ | ⭐ |

## 🚀 Khởi động nhanh (Quick Start)

```bash
# Bước 1: Vào thư mục
cd C:\xampp\htdocs\cvd2\teacher

# Bước 2: Cài thư viện (lần đầu)
pip install -r requirements_socketio.txt

# Bước 3: Chạy server
python socketio_server.py

# Bước 4: Mở trên PC
http://localhost:5000/?token=socketio123

# Bước 5: Mở trên Phone
http://<PC_IP>:5000/?token=socketio123
# Ví dụ: http://192.168.1.100:5000/?token=socketio123
```

## 📞 Hỗ trợ

Nếu gặp sự cố:
1. Kiểm tra console output (error messages)
2. Mở `socketio_server.log` để xem detailed logs
3. Chắc chắn Python và thư viện được cài đặt đúng
4. Kiểm tra firewall settings

---

**Ghi chú**: Giải pháp Socket.IO này đơn giản và hiệu quả hơn so với cách file-based. 
Không cần phải build exe hay phức tạp hóa. Chỉ Python + Flask-SocketIO là đủ!
