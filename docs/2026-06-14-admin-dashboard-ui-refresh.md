# Cải thiện UI Admin Dashboard

Ngày thực hiện: 2026-06-14

## File thay đổi

- `admin/dashboard.php`
- `admin/assets/dashboard.css`

## Cấu trúc mới

- Header hiển thị trường, năm học, học kỳ và thời gian cập nhật.
- Bốn chỉ số vận hành chính:
  - Giáo viên và tình trạng phân công.
  - Học sinh hoạt động trong 7 ngày.
  - Lớp và môn học.
  - Tổng đề kiểm tra.
- Vùng thông tin cần chú ý:
  - Lượt ôn tập và tăng trưởng.
  - Yêu cầu Premium chờ duyệt.
  - Cảnh báo đăng nhập.
- Biểu đồ hoạt động theo môn học.
- Danh sách hoạt động gần đây.
- Tám thao tác quản trị thường dùng.
- Bảng tóm tắt đội ngũ giáo viên và trạng thái phân công.
- Bảng môn học được sử dụng nhiều.

## Điều chỉnh dữ liệu

- Tổng số đề được đếm đệ quy trong toàn bộ `teacher/exams`, thay vì chỉ thư mục `generated`.
- Bổ sung thống kê giáo viên đã/chưa hoàn tất phân công môn và lớp.
- Bổ sung tỷ lệ học sinh hoạt động trong bảy ngày.
- Đọc năm học và học kỳ từ `admin/system_config.json`.

## UI

- Tuân theo phong cách chuẩn của dự án:
  - Nền sáng, màu xanh chủ đạo.
  - Bảng và panel dễ quét.
  - Không sử dụng hiệu ứng phóng to hoặc thẻ trang trí quá mức.
  - Cảnh báo và thao tác có thứ bậc rõ ràng.
  - Responsive cho desktop, tablet và điện thoại.

## Kết quả kiểm tra

- PHP hợp lệ.
- Render qua Apache: HTTP 200.
- Stylesheet trả về HTTP 200.
- JavaScript sau khi PHP render hợp lệ.
- Không có ID HTML trùng.
- Render đủ 4 thẻ chỉ số và 8 thao tác nhanh.
- Chưa chụp ảnh trực quan vì trình duyệt tích hợp không khả dụng trong phiên làm việc.

