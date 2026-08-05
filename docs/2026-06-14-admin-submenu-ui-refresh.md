# Cải thiện submenu khu vực quản trị

## Trạng thái

- Hoàn thành: 2026-06-14
- Phạm vi: menu điều hướng dùng chung cho toàn bộ trang admin

## File thay đổi

- `admin/navbar.php`
- `admin/assets/admin-navbar.css`

## Nội dung thực hiện

- Chuẩn hóa lại toàn bộ tiếng Việt bị lỗi mã hóa trong menu cũ.
- Thay emoji bằng Bootstrap Icons đồng nhất với giao diện quản trị.
- Thêm trạng thái active cho cả trang hiện tại và nhóm submenu chứa trang đó.
- Bổ sung tiêu đề, mô tả và icon cho từng chức năng trong submenu.
- Tách rõ nhóm dữ liệu, thống kê, hệ thống và tài khoản.
- Cải thiện khu vực tài khoản quản trị và thao tác đăng xuất.
- Thiết kế submenu desktop dạng bảng điều hướng rõ ràng.
- Thiết kế submenu mobile dạng danh sách mở rộng, hỗ trợ cuộn khi chiều cao màn hình thấp.
- Giữ nguyên URL và hành vi điều hướng hiện có.

## Kiểm tra

- Kiểm tra cú pháp PHP cho `admin/navbar.php`.
- Kiểm tra lỗi khoảng trắng bằng `git diff --check`.
- Kiểm tra render trang dashboard qua HTTP.
