# 2026-06-05 - Restore Slide Templates

## Vấn đề

Trang slide đã từng có nhiều template mẫu, nhưng hiện tại người dùng không thấy đầy đủ như trước.

## Kiểm tra

- File dữ liệu template vẫn còn:
  - `data/html_templates_metadata.json`
  - `data/slide_templates/`
  - `data/slide_templates.json`
  - `data/slide_templates_complete.json`
- `data/html_templates_metadata.json` hợp lệ:
  - 52 templates
  - 10 categories
  - 0 file template bị thiếu
- `teacher/api/get_template.php` đọc được template từ file HTML thật.

## Nguyên nhân

- `teacher/slides.php` bản hiện tại không còn section hiển thị danh sách template mẫu như `slides_OLD_BACKUP.php`.
- `teacher/slide_builder.php` bản hiện tại không còn xử lý `?template=...` như flow cũ.
- Khi tạo presentation mới, slides ban đầu rỗng. Nếu bấm `Chọn Template`, hàm JavaScript cũ thoát khi `currentSlideIndex < 0`, làm template không áp được nếu chưa có slide đang chọn.

## Đã sửa

- `teacher/slides.php`
  - Load template từ `data/html_templates_metadata.json`.
  - Hiển thị section `Templates HTML Mẫu`.
  - Hiển thị số lượng template.
  - Thêm filter theo category.
  - Nút `Sử dụng Template` mở `slide_builder.php?template={id}`.

- `teacher/slide_builder.php`
  - Thêm hàm `loadHtmlSlideTemplate()`.
  - Hỗ trợ `?template=...` để tạo presentation mới với slide đầu tiên từ template.
  - Khi chưa có slide đang chọn mà bấm `Chọn Template`, tự tạo slide mới thay vì báo lỗi và thoát.

## Kiểm tra đã chạy

```text
php -l .\cvdlms\teacher\slide_builder.php
php -l .\cvdlms\teacher\slides.php
```

Kết quả:

```text
No syntax errors
52 templates / 10 categories
```
