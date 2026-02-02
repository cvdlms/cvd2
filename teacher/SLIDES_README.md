# 📊 HỆ THỐNG QUẢN LÝ SLIDES CVD2

## 🎯 TỔNG QUAN

Hệ thống quản lý slides với **2 chức năng chính**:

### 1️⃣ Import PowerPoint
- Upload file PPT/PPTX
- Xem online qua Microsoft Office Online Viewer
- Quản lý, download, xóa file
- Không cần parse hoặc convert

### 2️⃣ Tạo HTML Slides
- Tạo slides từ code HTML/CSS/JS
- 12 templates mẫu đa dạng
- Code Editor chuyên nghiệp (CodeMirror)
- **KHÔNG có WYSIWYG Editor** - chỉ chỉnh sửa code
- Live preview trong khi code
- Syntax highlighting, auto-completion

---

## 📁 CẤU TRÚC FILE

```
cvd2/
├── teacher/
│   ├── slides.php                     # Trang chính - Quản lý tất cả slides
│   ├── import_pptx.php                # Upload PowerPoint
│   ├── slide_builder.php              # Code Editor cho HTML slides
│   └── api/
│       ├── save_html_slide.php        # Lưu HTML slide
│       ├── get_templates.php          # Danh sách templates
│       ├── get_template.php           # Nội dung template
│       ├── delete_html_slide.php      # Xóa HTML slide
│       └── update_ppt_views.php       # Cập nhật lượt xem PPT
├── data/
│   ├── ppt_metadata.json              # Metadata của PPT files
│   └── html_slides_metadata.json      # Metadata của HTML slides
└── uploads/
    ├── ppt_files/                     # Lưu file PowerPoint
    └── html_slides/                   # Lưu HTML slides
```

---

## 🚀 HƯỚNG DẪN SỬ DỤNG

### A. IMPORT POWERPOINT

#### Bước 1: Truy cập trang upload
```
/cvd2/teacher/import_pptx.php
```

#### Bước 2: Upload file PPT/PPTX
- **Kéo thả** file vào vùng upload, hoặc
- **Click** nút "Chọn File"
- Giới hạn: 100MB

#### Bước 3: Nhập thông tin
- **Tiêu đề** (bắt buộc): Tên bài giảng
- **Mô tả**: Mô tả ngắn gọn
- **Môn học**: Chọn môn
- **Tags**: Các từ khóa (cách nhau bởi dấu phẩy)

#### Bước 4: Click "Upload PowerPoint"

#### Bước 5: Xem file
- Click nút "**Xem**" để mở qua Microsoft Office Online
- File sẽ hiển thị trong iframe
- Click "**Tải**" để download file gốc
- Click "**Xóa**" để xóa file

---

### B. TẠO HTML SLIDES

#### Bước 1: Chọn template
Vào `/cvd2/teacher/slide_builder.php`

Click nút "**Templates**" để chọn 1 trong 12 templates:

1. **Blank Slide** - Slide trống
2. **Title Slide** - Tiêu đề lớn
3. **Content Slide** - Danh sách nội dung
4. **Two Columns** - 2 cột so sánh
5. **Image + Text** - Hình + văn bản
6. **Code Slide** - Hiển thị code
7. **Quote Slide** - Trích dẫn
8. **Full Image** - Hình toàn màn hình
9. **Grid Layout** - Lưới 2x2
10. **Video Slide** - Embed video
11. **Interactive Slide** - Tương tác JavaScript
12. **Timeline** - Dòng thời gian

#### Bước 2: Chỉnh sửa code
- Code tự động load vào editor
- Chỉnh sửa HTML/CSS/JS trực tiếp
- **Không có drag & drop UI** - chỉ code

#### Bước 3: Xem preview
- Click "**Refresh Preview**" để cập nhật
- Preview hiển thị bên phải
- Hoặc click "**Mở tab mới**" để xem fullscreen

#### Bước 4: Format code (tùy chọn)
- Click "**Format Code**" để tự động căn chỉnh
- Code sẽ được làm đẹp, dễ đọc

#### Bước 5: Lưu slide
- Nhập **tiêu đề** slide ở header
- Click "**Lưu Slide**"
- Slide được lưu vào database

#### Bước 6: Xem hoặc chỉnh sửa lại
- Vào `/cvd2/teacher/slides.php`
- Tab "HTML Slides"
- Click "**Sửa Code**" để chỉnh sửa
- Click "**Xem**" để xem trong tab mới

---

## 💻 CODE EDITOR - TÍNH NĂNG

### Syntax Highlighting
- Màu sắc rõ ràng cho HTML, CSS, JS
- Theme: Monokai (dark theme chuyên nghiệp)

### Auto-completion
- Tự động đóng tags: `<div>` → `</div>`
- Tự động đóng brackets: `{` → `}`
- Tự động đóng quotes: `"` → `"`

### Keyboard Shortcuts
- **Ctrl + S** hoặc **Cmd + S**: Lưu slide
- **F11**: Fullscreen
- **Tab**: Thụt lề
- **Ctrl + Z**: Undo
- **Ctrl + Y**: Redo

### Live Preview
- Preview tự động cập nhật khi code thay đổi
- Debounce 500ms để tránh lag
- Hiển thị chính xác như khi xem thật

---

## 🎨 CHỈNH SỬA TEMPLATES

Mỗi template là **file HTML hoàn chỉnh**. Bạn có thể:

### 1. Thay đổi màu sắc
```css
body {
    background: linear-gradient(135deg, #667eea, #764ba2);
}
```

### 2. Thêm hình ảnh
```html
<img src="https://your-image-url.com/image.jpg" alt="Description">
```

### 3. Embed YouTube video
```html
<iframe src="https://www.youtube.com/embed/YOUR_VIDEO_ID" 
        allowfullscreen></iframe>
```

### 4. Thêm animation
```css
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.slide {
    animation: fadeIn 1s ease-in;
}
```

### 5. Thêm JavaScript tương tác
```javascript
function handleClick() {
    alert('Hello!');
}
```

### 6. Responsive design
```css
@media (max-width: 768px) {
    h1 { font-size: 2rem; }
}
```

---

## 📊 MICROSOFT OFFICE ONLINE VIEWER

### Cách hoạt động
Khi click "Xem" một file PPT, hệ thống:

1. Tạo URL đầy đủ của file:
```
https://your-domain.com/cvd2/uploads/ppt_files/ppt_abc123.pptx
```

2. Mở qua Microsoft Office Online:
```
https://view.officeapps.live.com/op/embed.aspx?src=[FILE_URL]
```

3. Hiển thị trong iframe modal

### Yêu cầu
- ✅ File phải truy cập được từ internet (public URL)
- ✅ Server phải cho phép CORS
- ✅ File phải là .ppt hoặc .pptx hợp lệ

### Lưu ý
- **Chỉ xem** - không chỉnh sửa được
- Hỗ trợ đầy đủ animations, transitions
- Load có thể hơi lâu với file lớn

---

## 🗂️ QUẢN LÝ FILE

### PowerPoint Files
```json
{
    "ppt_abc123": {
        "id": "ppt_abc123_1706774400",
        "title": "Bài Giảng Toán",
        "description": "Chương trình lớp 10",
        "original_filename": "bai-giang-toan.pptx",
        "stored_filename": "ppt_abc123_1706774400.pptx",
        "file_path": "uploads/ppt_files/ppt_abc123_1706774400.pptx",
        "file_size": 2048576,
        "file_size_formatted": "2.05 MB",
        "extension": "pptx",
        "subject_id": "1",
        "tags": ["Toán", "Lớp 10"],
        "teacher_username": "teacher1",
        "teacher_fullname": "Nguyễn Văn A",
        "created_at": "2026-02-01 10:00:00",
        "updated_at": "2026-02-01 10:00:00",
        "views": 15,
        "downloads": 3
    }
}
```

### HTML Slides
```json
{
    "slide_xyz789": {
        "id": "slide_xyz789_1706774500",
        "title": "Giới Thiệu Python",
        "filename": "slide_xyz789_1706774500.html",
        "file_path": "uploads/html_slides/slide_xyz789_1706774500.html",
        "teacher_username": "teacher1",
        "created_at": "2026-02-01 10:05:00",
        "updated_at": "2026-02-01 10:05:00",
        "views": 8
    }
}
```

---

## 🎯 USE CASES

### 1. Giảng dạy Lập Trình
- Sử dụng **Code Slide** template
- Hiển thị code với syntax highlighting
- Demo code thực tế

### 2. Bài thuyết trình có sẵn
- Upload file PPT từ trước
- Xem online không cần cài PowerPoint
- Học sinh xem trên bất kỳ thiết bị nào

### 3. Slide tương tác
- Template **Interactive Slide**
- Thêm JavaScript cho quiz, game
- Tạo trải nghiệm học tập tương tác

### 4. Timeline sự kiện
- Template **Timeline**
- Hiển thị lịch sử, quá trình
- Phù hợp môn Sử, Văn

### 5. So sánh khái niệm
- Template **Two Columns**
- So sánh 2 phương pháp, lý thuyết
- Layout rõ ràng, dễ hiểu

---

## 🔧 CUSTOMIZATION

### Tạo template riêng
Thêm vào file `api/get_template.php`:

```php
'my-custom' => '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>My Custom Template</title>
    <style>
        /* Your custom CSS */
    </style>
</head>
<body>
    <!-- Your custom HTML -->
    <script>
        // Your custom JavaScript
    </script>
</body>
</html>'
```

Và thêm vào `api/get_templates.php`:
```php
[
    'id' => 'my-custom',
    'name' => 'My Custom Template',
    'description' => 'My custom slide template',
    'icon' => '🎨'
]
```

---

## 📦 DEPENDENCIES

### Frontend
- **CodeMirror 5.65.2** - Code editor
- **js-beautify 1.14.0** - Code formatting
- **Font Awesome 6.4.0** - Icons
- **Bootstrap Icons 1.10.0** - Additional icons

### Backend
- **PHP 7.4+**
- **JSON** - Data storage
- File system access

### External Services
- **Microsoft Office Online** - PPT viewer
- **Highlight.js** - Code syntax highlighting (trong templates)
- **YouTube** - Video embedding (tùy chọn)

---

## 🚨 TROUBLESHOOTING

### PPT không hiển thị trong viewer?
- ✅ Kiểm tra file có public URL
- ✅ Thử tải về và mở bằng PowerPoint
- ✅ Đảm bảo file không bị corrupt
- ✅ Kiểm tra firewall/CORS settings

### Code Editor lag khi nhập?
- ✅ Debounce đã set 500ms
- ✅ Tắt live preview nếu code quá dài
- ✅ Format code để giảm dung lượng

### Preview không cập nhật?
- ✅ Click "Refresh Preview"
- ✅ Kiểm tra JavaScript console có lỗi không
- ✅ Thử "Mở tab mới" để xem

### File upload lỗi?
- ✅ Kiểm tra kích thước < 100MB
- ✅ Đảm bảo thư mục `uploads/` có quyền write
- ✅ Kiểm tra `php.ini`: `upload_max_filesize`, `post_max_size`

---

## 🔐 BẢO MẬT

### File Upload
- ✅ Validate file type (.ppt, .pptx only)
- ✅ Validate file size (max 100MB)
- ✅ Unique filename (prevent overwrite)
- ✅ Store outside web root (recommended)

### HTML Slides
- ⚠️ **Lưu ý**: HTML slides cho phép chạy JavaScript
- ⚠️ Chỉ nên dùng trong môi trường tin cậy
- ⚠️ Giáo viên tự chịu trách nhiệm về code

### Session
- ✅ Session check trên mọi trang
- ✅ Verify ownership trước khi edit/delete
- ✅ Separate session name: `CVD_TEACHER_SESSION`

---

## 📈 ROADMAP

### Phase 2 (Future)
- [ ] Export HTML slide to PDF
- [ ] Chia sẻ slide với học sinh
- [ ] Analytics: views, time spent
- [ ] Slide templates marketplace
- [ ] Collaborative editing
- [ ] Version control cho slides
- [ ] AI-generated slide suggestions

---

## 📞 SUPPORT

Nếu cần hỗ trợ:
1. Xem file backup: `*_OLD_BACKUP.php`
2. Kiểm tra PHP error logs
3. Browser console để debug JavaScript
4. Liên hệ admin

---

## 📝 CHANGELOG

### Version 2.0.0 - 2026-02-01
✅ **BREAKING CHANGES**:
- Loại bỏ WYSIWYG editor
- PPT chỉ upload & xem (không parse)
- Code editor only

✅ **Tính năng mới**:
- Microsoft Office Online Viewer
- 12 HTML templates
- Code Editor với CodeMirror
- Syntax highlighting
- Live preview
- Format code
- Metadata system
- Tab-based interface

✅ **Files mới**:
- `slides.php` - Trang quản lý chính
- `import_pptx.php` - Upload PPT
- `slide_builder.php` - Code editor
- `api/save_html_slide.php`
- `api/get_templates.php`
- `api/get_template.php`
- `api/delete_html_slide.php`
- `api/update_ppt_views.php`

✅ **Backup files**:
- `slides_OLD_BACKUP.php`
- `import_pptx_OLD_BACKUP.php`
- `slide_builder_OLD_BACKUP.php`

---

**Phát triển bởi:** CVD2 Development Team  
**Ngày cập nhật:** 01/02/2026  
**Phiên bản:** 2.0.0
