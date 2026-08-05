# Cải thiện quản lý giáo viên

Ngày thực hiện: 2026-06-14

## Phạm vi

- `admin/manage_teachers.php`
- `admin/assets/manage_teachers.css`

## Giao diện mới

- Bổ sung số liệu tổng giáo viên, đã phân công môn, đã phân công lớp và chưa đủ phân công.
- Bảng giáo viên hiển thị:
  - Họ tên, tài khoản.
  - Email và ngày sinh.
  - Môn phụ trách.
  - Lớp phụ trách.
  - Trạng thái phân công.
- Bổ sung tìm kiếm theo tên, username, email.
- Bổ sung lọc theo môn, khối và trạng thái phân công.
- Gom thao tác vào menu ba chấm để bảng gọn hơn.
- Responsive cho màn hình nhỏ.
- Chuẩn hóa modal với icon ngữ cảnh, tiêu đề, mô tả và typography thống nhất.
- Modal thêm giáo viên có nội dung cuộn riêng, header/footer cố định trong màn hình.

## Quy trình quản lý

- Modal thêm giáo viên có:
  - Thông tin tài khoản.
  - Hiện/ẩn mật khẩu.
  - Phân công môn và lớp ban đầu.
- Modal phân công chuyên môn hợp nhất môn học và lớp học.
- Có chọn tất cả lớp theo từng khối và bộ đếm số mục đã chọn.
- Modal đặt lại mật khẩu được Việt hóa.
- Xóa giáo viên chuyển từ URL GET sang form POST có xác nhận.
- Nhập Excel/CSV có bước xem trước tại trình duyệt.
- Tài khoản trùng khi nhập được bỏ qua, không ghi đè.

## Củng cố backend

- Thêm CSRF token cho toàn bộ thao tác POST.
- Chuẩn hóa username thành chữ thường.
- Kiểm tra định dạng username.
- Khi đổi username, chuyển đồng thời:
  - Hồ sơ tài khoản.
  - Phân công môn.
  - Phân công lớp.
- Ghi các file dữ liệu có khóa file và phục hồi nội dung cũ nếu một bước ghi thất bại.
- Tạo backup trước khi:
  - Xóa giáo viên.
  - Nhập giáo viên hàng loạt.

Backup được lưu tại:

`backups/teacher_operations/`

## Kết quả kiểm tra

- PHP hợp lệ.
- Trang render HTTP 200 với 18 giáo viên.
- JavaScript sau khi render hợp lệ.
- Không có ID HTML trùng.
- Kiểm thử trên bản sao dữ liệu:
  - Đổi username chuyển đúng tài khoản, môn và 19 lớp được phân công.
  - Lưu phân công môn/lớp mới thành công.
  - Xóa giáo viên bằng POST thành công.
  - Backup trước khi xóa được tạo thành công.
