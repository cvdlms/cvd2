# Chuyển lớp và nhập học sinh an toàn

Ngày thực hiện: 2026-06-14

## Mục tiêu

Hệ thống chỉ quản lý học sinh đang sử dụng chức năng kiểm tra đánh giá. Không xây dựng hồ sơ biến động học sinh theo mô hình quản lý nhà trường.

## Chức năng chuyển lớp

Trang `admin/manage_students.php` có thêm nút **Chuyển lớp**.

Quy trình:

1. Hệ thống đề xuất ánh xạ theo tên lớp:
   - 6A1 sang 7A1.
   - 7A1 sang 8A1.
   - 8A1 sang 9A1.
   - Khối 9 loại khỏi danh sách đang hoạt động.
2. Admin có thể thay đổi lớp đích của từng lớp.
3. Admin có thể thêm ngoại lệ cho một học sinh chuyển sang lớp khác.
4. Bắt buộc xem trước số lượng chuyển, loại và giữ nguyên.
5. Bắt buộc nhập `CHUYEN LOP` trước khi thực hiện.
6. Hệ thống tạo backup rồi mới cập nhật `class_id`.
7. STT được chuẩn hóa lại trong từng lớp.

Học sinh có `class_id` không tồn tại được cảnh báo và giữ nguyên.

## Chức năng nhập học sinh hàng loạt

Luồng nhập Excel/CSV được chuyển sang API hàng loạt:

- Chỉ thêm học sinh mới.
- Mã đã tồn tại được bỏ qua, không cập nhật dữ liệu cũ.
- Mã trùng trong cùng file được bỏ qua.
- Mã lớp không tồn tại hoặc thiếu dữ liệu được báo lỗi.
- Mã học sinh được chuẩn hóa bằng cách bỏ khoảng trắng và chuyển thành chữ hoa.
- Dữ liệu được kiểm tra toàn bộ trước khi ghi.
- Bắt buộc nhập `NHAP HOC SINH`.
- Tạo backup trước khi ghi.
- Chỉ ghi `students.json` một lần theo cơ chế file tạm và thay thế.
- Học sinh mới có mật khẩu mặc định `123456`.

Cột mã học sinh trong Excel nên đặt định dạng Text để không mất số 0 ở đầu.

## File đã thay đổi

- `admin/manage_students.php`
- `admin/api/student_bulk_common.php`
- `admin/api/bulk_promote_students.php`
- `admin/api/bulk_import_students.php`

## Backup

Backup được lưu tại:

`backups/student_operations/`

Tên file có dạng:

- `students_promote_YYYY-MM-DD_HHMMSS.json`
- `students_bulk-import_YYYY-MM-DD_HHMMSS.json`

## Kết quả kiểm tra

- Tất cả file PHP hợp lệ.
- Trang quản lý render HTTP 200.
- JavaScript sau khi render hợp lệ và không có ID HTML trùng.
- API xem trước chuyển lớp chạy thành công trên dữ liệu hiện tại.
- API nhập thử phát hiện đúng học sinh mới, trùng mã và sai lớp.
- Thao tác ghi được kiểm thử trên bản sao độc lập:
  - Chuyển 45 học sinh thành công.
  - Nhập thêm 1 học sinh.
  - Mã đã tồn tại không bị ghi đè.
  - Hai file backup được tạo thành công.

